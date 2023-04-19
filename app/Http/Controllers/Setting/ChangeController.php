<?php

namespace App\Http\Controllers\Setting;

use App\Clients\KassClient;
use App\Http\Controllers\BD\getMainSettingBD;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ChangeController extends Controller
{
    public function getChange(Request $request, $accountId){
        $isAdmin = $request->isAdmin;

        $SettingBD = new getMainSettingBD($accountId);
        $Config = Config::get("Global");

        $client = new KassClient($accountId);



        try {
             $json = json_decode(($client->posReport())->getBody()->getContents());
             $message =
                 'Наименование организации: '.$json->data->company_name.PHP_EOL.
                 "Идентификатор кассы: ".$json->data->pos_id.PHP_EOL.
                 "Серийный / заводской номер: ".$json->data->factory_number.PHP_EOL.
                 "Наличных в кассе: ".$json->data->balance.PHP_EOL;
        } catch (BadResponseException $e){
            $json = json_decode($e->getResponse()->getBody()->getContents());
            if ($e->getCode() == 401){
                return view('main.change', [
                    'accountId' => $accountId,
                    'isAdmin' => $request->isAdmin,

                    'message' => "Токен не действительный, пожалуйста введите данные заново " ,
                    'token' => null,
                    'idKassa' => null,
                ]);
            }

            if (property_exists($json,'error')){
                return view('main.change', [
                    'accountId' => $accountId,
                    'isAdmin' => $request->isAdmin,

                    'message' => (string) $json->error->message,
                    'token' => null,
                    'idKassa' => $request->idKassa,
                ]);
            } else  return view('main.change', [
                'accountId' => $accountId,
                'isAdmin' => $request->isAdmin,

                'message' => (string) $json ,
                'token' => null,
                'idKassa' => $request->idKassa,
            ]);
        }

        return view('main.change', [
            'accountId' => $accountId,
            'isAdmin' => $isAdmin,

            'message' => $message,
            'idKassa' => $SettingBD->idKassa,
        ]);

    }


    public function MoneyOperation(Request $request, $accountId): array
    {
        $Client = new KassClient($accountId);
        try {
            $body = json_decode(($Client->moneyPlacement($request->Sum, $request->OperationType))->getBody()->getContents());
           // dd($body);
            $message = "";
            if ($request->OperationType == 0){
                $message = "Изъятие из кассу наличных на сумму: ".$request->Sum.' '.PHP_EOL." Наличных осталось в кассе: ".$body->data->balance;
            } elseif ($request->OperationType == 1) {
                $message = "Внесение в кассу наличных на сумму: ".$request->Sum.' '.PHP_EOL." Наличных осталось в кассе: ".$body->data->balance;
            }

            return [
                'statusCode' => 200,
                'message' => $message,
            ];
        } catch (BadResponseException $e){
            $body = json_decode(($e->getResponse()->getBody()->getContents()));

            if (property_exists($body, 'error')){
                return [
                    'statusCode' => 500,
                    'message' => $body->error->message,
                ];
            } else return [
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ];
        }
    }

}
