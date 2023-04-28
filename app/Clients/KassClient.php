<?php

namespace App\Clients;

use App\Http\Controllers\BD\getMainSettingBD;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class KassClient
{
    private Client $client;
    private mixed $URL;
    private getMainSettingBD $Setting;

    public function __construct($accountId)
    {
        $this->URL = Config::get("Global");
        $this->Setting = new getMainSettingBD($accountId);

        $this->client = new Client([
            'base_uri' => $this->URL['kassa'].'api/v1',
            'headers' => [
                'Authorization' => "Bearer ".$this->Setting->authtoken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Pos-Id' => $this->Setting->idKassa
            ]
        ]);
    }

    public function loginToken($login, $password): \Psr\Http\Message\ResponseInterface
    {
        $client = new Client();
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        $body = json_encode([ "phone_number" => $login, "password" => $password ]);
        $request = new Request('POST', $this->URL['kassa'].'api/v1/login', $headers, $body);
        return $client->sendRequest($request);
    }




    public function posReport(): \Psr\Http\Message\ResponseInterface
    {
        return $res = $this->client->get($this->URL['kassa'].'api/v1/pos/report' );
    }

    public function XReport(): \Illuminate\Http\JsonResponse|\Psr\Http\Message\ResponseInterface
    {
        return $res = $this->client->get($this->URL['kassa'].'api/v1/pos/report/');
    }

    public function moneyPlacement($sum, $type): \Psr\Http\Message\ResponseInterface
    {
        return $res = $this->client->post($this->URL['kassa'].'api/v1/money/placement',[
            'body' => json_encode([
                'sum' => $sum,
                'type' => $type,
            ]),
        ]);
    }

    public function ZReport(): \Psr\Http\Message\ResponseInterface
    {
        return $res = $this->client->post($this->URL['kassa'].'api/v1/close/shift');
    }

    public function sale($body): \Psr\Http\Message\ResponseInterface
    {

       return $res = $this->client->request('GET',$this->URL['kassa'].'api/v1/sale?', ['query'=>$body]);
    }

    public function posShow(): \Psr\Http\Message\ResponseInterface
    {
        return $res = $this->client->request('GET',$this->URL['kassa'].'api/v1/pos/'.$this->Setting->idKassa.'/show');
    }

    public function unit($UOM): int
    {
        try {
            $Body = $this->client->get($this->URL['kassa'].'api/v2/units');

            $res = 1;

            foreach (json_decode($Body->getBody()->getContents())->data as $item){
                if ($item->code == $UOM) {
                    $res = $item->id;
                }
            }

            return $res;
        } catch (BadResponseException $e){
            return 1;
        }

    }

    public function CheckToken($UOM): \Psr\Http\Message\ResponseInterface
    {
            return $this->client->get($this->URL['kassa'].'api/v2/units');

    }

    public function get($url): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->get($url);

    }

}
