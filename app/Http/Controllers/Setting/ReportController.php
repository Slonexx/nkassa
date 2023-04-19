<?php

namespace App\Http\Controllers\Setting;

use App\Clients\KassClient;
use App\Http\Controllers\BD\getMainSettingBD;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function XReport(Request $request, $accountId): \Illuminate\Http\JsonResponse
    {
        $Client = new KassClient($accountId);
        try {
            $body = $Client->XReport();
            return response()->json([
                'Data'=> json_decode($body->getBody()->getContents())->data,
                "statusCode"=>200], 200);
        } catch (BadResponseException $e){
            $body = json_decode(($e->getResponse()->getBody()->getContents()));
            if (property_exists($body, 'error')){
                return  response()->json([
                    'statusCode' => 500,
                    'message' => $body->error->message,
                ]);
            } else return  response()->json([
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function ZReport(Request $request, $accountId): \Illuminate\Http\JsonResponse
    {
        $Client = new KassClient($accountId);
        try {
            $body = $Client->ZReport();
            return response()->json([
                'Data'=> json_decode($body->getBody()->getContents())->data,
                "statusCode"=>200], 200);
        } catch (BadResponseException $e){
            $body = json_decode(($e->getResponse()->getBody()->getContents()));
            if (property_exists($body, 'error')){
                return  response()->json([
                    'statusCode' => 500,
                    'message' => $body->error->message,
                ]);
            } else return  response()->json([
                'statusCode' => 500,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
