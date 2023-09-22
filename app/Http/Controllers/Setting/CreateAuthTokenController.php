<?php

namespace App\Http\Controllers\Setting;

use App\Clients\KassClient;
use App\Http\Controllers\BD\getMainSettingBD;
use App\Http\Controllers\Config\getSettingVendorController;
use App\Http\Controllers\Config\Lib\AppInstanceContoller;
use App\Http\Controllers\Config\Lib\cfg;
use App\Http\Controllers\Config\Lib\VendorApiController;
use App\Http\Controllers\Controller;
use App\Services\workWithBD\DataBaseService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class CreateAuthTokenController extends Controller
{
    public function getCreateAuthToken(Request $request, $accountId): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {
        $isAdmin = $request->isAdmin;
        $SettingBD = new getMainSettingBD($accountId);

        return view('setting.authToken', [
            'accountId' => $accountId,
            'isAdmin' => $isAdmin,

            'token' => $SettingBD->authtoken,
            'idKassa' => $SettingBD->idKassa,
            'section_id' => $SettingBD->section_id,
        ]);
    }

    public function postCreateAuthToken(Request $request, $accountId): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Contracts\Foundation\Application
    {
        $Setting = new getSettingVendorController($accountId);
        $SettingBD = new getMainSettingBD($accountId);
        $Client = new KassClient($accountId);

        if ($SettingBD->tokenMs == null) {
            DataBaseService::createMainSetting($accountId, $Setting->TokenMoySklad, $request->token, $request->idKassa , $request->section_id);
        } else {
            DataBaseService::updateMainSetting($accountId, $Setting->TokenMoySklad, $request->token, $request->idKassa, $request->section_id);
        }


        try {
            $UOM = $Client->CheckToken(796);
        } catch (BadResponseException $e) {
            $json = json_decode($e->getResponse()->getBody()->getContents());
            if ($e->getCode() == 401){
                return view('setting.authToken', [
                    'accountId' => $accountId,
                    'isAdmin' => $request->isAdmin,

                    'message' => "Токен не действительный, пожалуйста введите данные заново " ,
                    'token' => null,
                    'idKassa' => $request->idKassa,
                    'section_id' => $SettingBD->section_id,
                ]);
            }

           if (property_exists($json,'error')){
               return view('setting.authToken', [
                   'accountId' => $accountId,
                   'isAdmin' => $request->isAdmin,

                   'message' => (string) $json->error->message,
                   'token' => null,
                   'idKassa' => $request->idKassa,
                   'section_id' => $request->section_id,
               ]);
           } else  return view('setting.authToken', [
               'accountId' => $accountId,
               'isAdmin' => $request->isAdmin,

               'message' => (string) $json ,
               'token' => null,
               'idKassa' => $request->idKassa,
               'section_id' => $request->section_id,
           ]);
        }

        try {
            $Client->moneyPlacement(100, 1);
        } catch (BadResponseException $e){
            $json = json_decode($e->getResponse()->getBody()->getContents());

            if ($e->getCode() == 401){
                return view('setting.authToken', [
                    'accountId' => $accountId,
                    'isAdmin' => $request->isAdmin,

                    'message' => "Токен не действительный, пожалуйста введите данные заново " ,
                    'token' => null,
                    'idKassa' => $request->idKassa,
                    'section_id' => $SettingBD->section_id,
                ]);
            }

            if (property_exists($json,'error')){
                return view('setting.authToken', [
                    'accountId' => $accountId,
                    'isAdmin' => $request->isAdmin,

                    'message' => (string) $json->error->message,
                    'token' => $request->token,
                    'idKassa' => null,
                    'section_id' => $request->section_id,
                ]);
            } else  return view('setting.authToken', [
                'accountId' => $accountId,
                'isAdmin' => $request->isAdmin,

                'message' => (string) $json ,
                'token' => $request->token,
                'idKassa' => null,
                'section_id' => $request->section_id,
            ]);
        }

        try {
            $body = [
                'status' => 1,
                'payment' => [0=>[
                    'payment_method_id'=> 1,
                    'sum'=> 1,
                ]],
                'goods' => [0 => [
                    'name'=> "Проверка токена",
                    'price'=> 1,
                    'quantity'=> 1,
                    'unit_id'=> $UOM,
                    'section_id'=> (int) $request->section_id,
                    'markup'=> 0,
                    'discount'=> 0,
                    'vat'=> 0,
                ]]
            ];
            $body = $Client->sale($body);
        } catch (BadResponseException $e){
            $json = json_decode($e->getResponse()->getBody()->getContents());
            if (property_exists($json,'error')){
                return view('setting.authToken', [
                    'accountId' => $accountId,
                    'isAdmin' => $request->isAdmin,

                    'message' => (string) $json->error->message,
                    'token' => $request->token,
                    'idKassa' => $request->idKassa,
                    'section_id' => null,
                ]);
            } else  return view('setting.authToken', [
                'accountId' => $accountId,
                'isAdmin' => $request->isAdmin,

                'message' => (string) $json ,
                'token' => $request->token,
                'idKassa' => $request->idKassa,
                'section_id' => null,
            ]);
        }

        $cfg = new cfg();
        $app = AppInstanceContoller::loadApp($cfg->appId, $accountId);
        $app->status = AppInstanceContoller::ACTIVATED;
        $vendorAPI = new VendorApiController();
        $vendorAPI->updateAppStatus($cfg->appId, $accountId, $app->getStatusName());
        $app->persist();

        return to_route('getDocument', ['accountId' => $accountId, 'isAdmin' => $request->isAdmin]);
    }


    public function createAuthToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $login = str_replace('+', '', str_replace(" ", '', str_replace('-', '',
            str_replace('(', '', str_replace(')', '', $request->email))))) ;
        $password = str_replace(" ", '', $request->password) ;

        dd($request->all(),$login,  $password);

        $client = new KassClient("");
        try {
            $post = json_decode($client->loginToken($login, $password)->getBody()->getContents());
            if (property_exists($post, 'error')) {
                $result = [
                    'status' => 500,
                    'error' => ($post),
                    'auth_token' => null,
                ];
            }
            else $result = [
                'status' => 200,
                'auth_token' => $post->data->api_token,
            ];
        } catch (BadResponseException $e){
            $result = [
                'status' => $e->getCode(),
                'auth_token' => null,
            ];
        }

        return response()->json($result);
}
}
