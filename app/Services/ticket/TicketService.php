<?php

namespace App\Services\ticket;

use App\Clients\KassClient;
use App\Clients\MsClient;
use App\Http\Controllers\BD\getMainSettingBD;
use App\Services\AdditionalServices\DocumentService;
use App\Services\MetaServices\MetaHook\AttributeHook;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Str;

class   TicketService
{

    private AttributeHook $attributeHook;
    private DocumentService $documentService;
    private MsClient $msClient;
    private getMainSettingBD $Setting;
    private KassClient $kassClient;

    public function __construct(AttributeHook $attributeHook, DocumentService $documentService){
        $this->attributeHook = $attributeHook;
        $this->documentService = $documentService;
    }


    public function createTicket($data): \Illuminate\Http\JsonResponse
    {
        $accountId = $data['accountId'];
        $id_entity = $data['id_entity'];
        $entity_type = $data['entity_type'];

        $money_card = $data['money_card'];
        $money_cash = $data['money_cash'];
        $money_mobile = 0;
        $payType = $data['pay_type'];
        $total = $data['total'];

        $positions = $data['positions'];



        $this->Setting = new getMainSettingBD($accountId);
        $this->kassClient = new KassClient($this->Setting->accountId);
        $this->msClient = new MsClient($this->Setting->tokenMs);

        try {
            $oldBody = $this->msClient->get('https://api.moysklad.ru/api/remap/1.2/entity/'.$entity_type.'/'.$id_entity);
        } catch (BadResponseException $e){
            return response()->json([
                'status'    => 'error',
                'code'      => $e->getCode(),
                'errors'    => json_decode($e->getResponse()->getBody()->getContents(), true)
            ]);
        }


        $Body = $this->setBodyToPostClient($id_entity, $entity_type, $money_card, $money_cash, $money_mobile, $payType, $total, $positions);

        if (isset($Body['Status'])) { return response()->json($Body['Message']); }

        try {
            $postTicket = json_decode($this->kassClient->sale($Body)->getBody()->getContents());
           /* $address = json_decode($this->kassClient->posShow()->getBody()->getContents());
            $html = $this->kassClient->get($postTicket->data->preview_link."&is_print=1");*/

            $result = json_decode(json_encode([
                'data' => $postTicket->data,
                //'pos' => $address->data->pos,
                //'payment' => $Body['payment'],
                //'html' => "<html> <head> </head> <body>" . $html->getBody()->getContents() . " </body> </html>",
            ]));

            $putBody = $this->putBodyMS($entity_type, $Body, $postTicket, $oldBody, $positions);
            $put =  $this->msClient->put('https://api.moysklad.ru/api/remap/1.2/entity/'.$entity_type.'/'.$id_entity, $putBody);

            if ($payType == 'return'){
                $this->createReturnDocument($put, $postTicket, $putBody, $entity_type);
                $put =  $this->msClient->put('https://api.moysklad.ru/api/remap/1.2/entity/'.$entity_type.'/'.$id_entity, [
                    'description' => $this->descriptionToCreate($oldBody, $postTicket, 'Возврат, фискальный номер: '),
                ]);
            }
            if ($this->Setting->paymentDocument != null ){
                $this->createPaymentDocument($this->Setting, $entity_type, $put, $Body['payment']);
            }

            return response()->json([
                'status'    => 'Ticket created',
                'code'      => 200,
                'postTicket' => $result,
            ]);
        } catch (BadResponseException  $e){
            $json = json_decode($e->getResponse()->getBody()->getContents(), true);
            //dd($json['error']);
            if (isset($json['error'])) {
                $json =  $json['error']['message'];
            }

            return response()->json([
                'status'    => 'error',
                'code'      => $e->getCode(),
                'errors'    => $json,
                'Body'      => $Body,
            ]);
        }

    }


    private function setBodyToPostClient(mixed $id_entity, mixed $entity_type, mixed $money_card, mixed $money_cash, mixed $money_mobile, mixed $payType, mixed $total, mixed $positions): array
    {

        //dd($this->Setting, $id_entity, $entity_type, $money_card, $money_cash, $payType, $total, $positions);

        $type = $this->getOperation($payType);
        $payments = $this->getPayments($money_card, $money_cash, $money_mobile, $total);
        $items = $this->getItems($positions, $id_entity, $entity_type);

        if ($type == '') return ['Status' => false, 'Message' => 'Не выбран тип продажи'];
        if ($payments == null) return ['Status' => false, 'Message' => 'Не были введены суммы !'];
        if ($items == null) return ['Status' => false, 'Message' => 'Отсутствуют позиции товара!'];


        $result = [
            "status" => $type,
            "payment" => $payments,
            "goods" => $items,

        ];

        return $result;
    }


    private function getOperation($payType): int|string
    {
        return match ($payType) {
            "sell" => 1,
            "return" => 2,
            default => "",
        };
    }

    private function getPayments($card, $cash, $mobile, $total): array
    {
        $result = null;
        $tmp= null;
        if ( $cash >= 0 ) {
            $tmp[] = [
                'payment_method_id' => 0,
                'sum' => (float) $cash,
            ];
        }
        if ( $card >= 0 ) {
            $tmp[] = [
                'payment_method_id' => 1,
                'sum' => (float) $card,
            ];
        }

        foreach ($tmp as $item){
            if ($item['sum'] > 0){
                $result[] = $item;
            }
        }

        return $result;
    }

    private function getItems($positions, $idObject, $typeObject): array
    {
        $result = null;

        foreach ($positions as $id => $item){
            $TaxPercent = (float) trim($item->is_nds, '%');
            $discount = trim($item->discount, '%');
            if ($TaxPercent == 'без НДС' or $TaxPercent == "0%" or $TaxPercent == 0 or $TaxPercent == "0") { $TaxPercent = 0; }
            if ($discount > 0){ $discount = round(($item->price * $item->quantity * ($discount/100)), 2); }
            if ($typeObject == 'demand'){
                $demand =  $this->msClient->get('https://api.moysklad.ru/api/remap/1.2/entity/' . $typeObject . '/' . $idObject);
                $demandPos =  $this->msClient->get($demand->positions->meta->href)->rows;

                foreach ($demandPos as $item_2){
                    if ( $item->id == $item_2->id and isset($item_2->trackingCodes) ){
                        foreach ($item_2->trackingCodes as $code){
                            $result[] = [
                                'name' => (string) str_replace('+', ' ', $item->name),
                                'price' => (float) round($item->price, 3),
                                'quantity' => (float) round($item->quantity, 3),

                                'unit_id' => (int) $this->codeUOM($item->UOM),
                                'section_id' => (int) $this->Setting->section_id,
                                //'markup' => 0,
                                'discount' =>(float) round($discount, 2),
                                'vat' => (int) $TaxPercent,
                                'excise_stamp' => (string) $code->cis,
                            ];
                        }

                    }
                }
            }
            else {
                $result[$id] = [
                    'name' => (string) str_replace('+', ' ', $item->name),
                    'price' => (float) round($item->price, 3),
                    'quantity' => (float) round($item->quantity, 3),

                    'unit_id' => (int) $this->codeUOM($item->UOM),
                    'section_id' => (int) $this->Setting->section_id,
                    //'markup' => 0,
                    'discount' =>(float) round($discount, 2),
                    'vat' => (int) $TaxPercent,
                ];
            }
        }

        return $result;
    }

    private function putBodyMS($entity_type, mixed $Body, mixed $postTicket, mixed $oldBody, mixed $positionsBody): array
    {   $result = null;
        $check_attributes_in_value_name = false;

        $attributes =  $this->msClient->get('https://api.moysklad.ru/api/remap/1.2/entity/'.$entity_type.'/metadata/attributes/')->rows;
        $positions =  $this->msClient->get($oldBody->positions->meta->href)->rows;
        if (property_exists($oldBody, 'attributes')) {
            foreach ($oldBody->attributes as $item){
                if ($item->name == 'Фискальный номер (Nurkassa)' and $item->name != ''){
                    $check_attributes_in_value_name = false;
                    break;
                } else $check_attributes_in_value_name = true;
            }
        } else $check_attributes_in_value_name = true;


        $Result_attributes = $this->setAttributesToPutBody($Body, $postTicket, $check_attributes_in_value_name, $attributes);
        $result['description'] = $this->descriptionToCreate($oldBody, $postTicket, 'Продажа, Фискальный номер: ');
        $Resul_positions = $this->setPositionsToPutBody($positions, $positionsBody);

        if ($Result_attributes != null){ $result['attributes'] = $Result_attributes; }
        if ($Resul_positions != null){ $result['positions'] = $Resul_positions; }

        return $result;
    }

    private function setAttributesToPutBody(mixed $Body, mixed $postTicket, bool $check_attributes, $attributes): array|null
    {
        $Result_attributes = null;
        foreach ($attributes as $item) {
            if ($item->name == "фискальный номер (Nurkassa)" and $check_attributes == true) {
                $Result_attributes[] = [
                    "meta"=> [
                        "href"=> $item->meta->href,
                        "type"=> $item->meta->type,
                        "mediaType"=> $item->meta->mediaType,
                    ],
                    "value" =>(string) $postTicket->data->params->receipt_number,
                ];
            }
            if ($item->name == "Ссылка для QR-кода (Nurkassa)" ) {
                $Result_attributes[] = [
                    "meta"=> [
                        "href"=> $item->meta->href,
                        "type"=> $item->meta->type,
                        "mediaType"=> $item->meta->mediaType,
                    ],
                    "value" => $postTicket->data->preview_link,
                ];
            }
            if ($item->name == "Фискализация (Nurkassa)" ) {
                $Result_attributes[] = [
                    "meta"=> [
                        "href"=> $item->meta->href,
                        "type"=> $item->meta->type,
                        "mediaType"=> $item->meta->mediaType,
                    ],
                    "value" => true,
                ];
            }
            if ($item->name == "ID (Nurkassa)" ) {
                $Result_attributes[] = [
                    "meta"=> [
                        "href"=> $item->meta->href,
                        "type"=> $item->meta->type,
                        "mediaType"=> $item->meta->mediaType,
                    ],
                    "value" => (string) $postTicket->data->params->sale_id,
                ];
            }
        }
        return $Result_attributes;
    }

    private function setPositionsToPutBody(mixed $positions, mixed $positionsBody): array
    {   $result = null;
        $sort = null;
        foreach ($positionsBody as $id=>$one){
            foreach ($positions as $item_p){
                if ($item_p->id == $one->id){
                    $sort[$id] = $item_p;
                }
            }
        }
        foreach ($positionsBody as $id=>$item){
            $result[$id] = [
                "id" => $item->id,
                "quantity" => (int) $item->quantity,
                "price" => (float) $item->price * 100,
                "discount" => (int) $item->discount,
                "vat" => (int) $item->is_nds,
                "assortment" => ['meta'=>[
                    "href" => $sort[$id]->assortment->meta->href,
                    "type" => $sort[$id]->assortment->meta->type,
                    "mediaType" => $sort[$id]->assortment->meta->mediaType,
                ]],
            ];
        }
        return $result;

    }

    private function createPaymentDocument( getMainSettingBD $Setting, string $entity_type, mixed $OldBody, mixed $payments)
    {
        switch ($Setting->paymentDocument){
            case "1": {
                $url = 'https://api.moysklad.ru/api/remap/1.2/entity/';
                if ($entity_type != 'salesreturn') {
                    $url = $url . 'cashin';
                } else {
                    //$url = $url . 'cashout';
                    break;
                }
                $body = [
                    'organization' => [  'meta' => [
                        'href' => $OldBody->organization->meta->href,
                        'type' => $OldBody->organization->meta->type,
                        'mediaType' => $OldBody->organization->meta->mediaType,
                    ] ],
                    'agent' => [ 'meta'=> [
                        'href' => $OldBody->agent->meta->href,
                        'type' => $OldBody->agent->meta->type,
                        'mediaType' => $OldBody->agent->meta->mediaType,
                    ] ],
                    'sum' => $OldBody->sum,
                    'operations' => [
                        0 => [
                            'meta'=> [
                                'href' => $OldBody->meta->href,
                                'metadataHref' => $OldBody->meta->metadataHref,
                                'type' => $OldBody->meta->type,
                                'mediaType' => $OldBody->meta->mediaType,
                                'uuidHref' => $OldBody->meta->uuidHref,
                            ],
                            'linkedSum' => $OldBody->sum
                        ], ]
                ];
                $this->msClient->post($url, $body);
                break;
            }
            case "2": {
                $url = 'https://api.moysklad.ru/api/remap/1.2/entity/';
                if ($entity_type != 'salesreturn') {
                    $url = $url . 'paymentin';
                } else {
                    //$url = $url . 'paymentout';
                    break;
                }

                $rate_body = $this->msClient->get("https://api.moysklad.ru/api/remap/1.2/entity/currency/")->rows;
                $rate = null;
                foreach ($rate_body as $item){
                    if ($item->name == "тенге" or $item->fullName == "Казахстанский тенге"){
                        $rate =
                            ['meta'=> [
                                'href' => $item->meta->href,
                                'metadataHref' => $item->meta->metadataHref,
                                'type' => $item->meta->type,
                                'mediaType' => $item->meta->mediaType,
                                ],
                        ];
                    }
                }

                $body = [
                    'organization' => [  'meta' => [
                        'href' => $OldBody->organization->meta->href,
                        'type' => $OldBody->organization->meta->type,
                        'mediaType' => $OldBody->organization->meta->mediaType,
                    ] ],
                    'agent' => [ 'meta'=> [
                        'href' => $OldBody->agent->meta->href,
                        'type' => $OldBody->agent->meta->type,
                        'mediaType' => $OldBody->agent->meta->mediaType,
                    ] ],
                    'sum' => $OldBody->sum,
                    'operations' => [
                        0 => [
                            'meta'=> [
                                'href' => $OldBody->meta->href,
                                'metadataHref' => $OldBody->meta->metadataHref,
                                'type' => $OldBody->meta->type,
                                'mediaType' => $OldBody->meta->mediaType,
                                'uuidHref' => $OldBody->meta->uuidHref,
                            ],
                            'linkedSum' => $OldBody->sum
                        ], ],
                    'rate' => $rate
                ];
                if ($body['rate'] == null) unlink($body['rate']);
                $this->msClient->post($url, $body);
                break;
            }
            case "3": {
                $url = 'https://api.moysklad.ru/api/remap/1.2/entity/';
                $url_to_body = null;
                foreach ($payments as $item){
                    if ($item['payment_method_id'] == 0){
                        if ($entity_type != 'salesreturn') {
                            $url_to_body = $url . 'cashin';
                        }
                    } else {
                        if ($entity_type != 'salesreturn') {
                            $url_to_body = $url . 'paymentin';
                        }
                    }

                    $rate_body =  $this->msClient->get("https://api.moysklad.ru/api/remap/1.2/entity/currency/")->rows;
                    $rate = null;
                    foreach ($rate_body as $item_rate){
                        if ($item_rate->name == "тенге" or $item_rate->fullName == "Казахстанский тенге"){
                            $rate =
                                ['meta'=> [
                                    'href' => $item_rate->meta->href,
                                    'metadataHref' => $item_rate->meta->metadataHref,
                                    'type' => $item_rate->meta->type,
                                    'mediaType' => $item_rate->meta->mediaType,
                                ],
                                ];
                        }
                    }

                    $body = [
                        'organization' => [  'meta' => [
                            'href' => $OldBody->organization->meta->href,
                            'type' => $OldBody->organization->meta->type,
                            'mediaType' => $OldBody->organization->meta->mediaType,
                        ] ],
                        'agent' => [ 'meta'=> [
                            'href' => $OldBody->agent->meta->href,
                            'type' => $OldBody->agent->meta->type,
                            'mediaType' => $OldBody->agent->meta->mediaType,
                        ] ],
                        'sum' => ($item['sum']) * 100,
                        'operations' => [
                            0 => [
                                'meta'=> [
                                    'href' => $OldBody->meta->href,
                                    'metadataHref' => $OldBody->meta->metadataHref,
                                    'type' => $OldBody->meta->type,
                                    'mediaType' => $OldBody->meta->mediaType,
                                    'uuidHref' => $OldBody->meta->uuidHref,
                                ],
                                'linkedSum' => ($item['sum']) * 100
                            ], ],
                        'rate' => $rate
                    ];
                    if ($body['rate'] == null) unlink($body['rate']);
                    $this->msClient->post($url_to_body, $body);
                }
                break;
            }
            case "4":{
                $url = 'https://api.moysklad.ru/api/remap/1.2/entity/';
                $url_to_body = null;
                foreach ($payments as $item){
                    $change = 0;
                    if ($item['payment_type'] == 0){
                        if ($entity_type != 'salesreturn') {
                            if ($Setting->OperationCash == 1) {
                                $url_to_body = $url . 'cashin';
                            }
                            if ($Setting->OperationCash == 2) {
                                $url_to_body = $url . 'paymentin';
                            }
                            if ($Setting->OperationCash == 0) {
                                continue;
                            }
                        }
                        if (isset($item['change'])) $change = $item['change'];
                    } else {
                        if ($entity_type != 'salesreturn') {
                            if ($Setting->OperationCard == 1) {
                                $url_to_body = $url . 'cashin';
                            }
                            if ($Setting->OperationCard == 2) {
                                $url_to_body = $url . 'paymentin';
                            }
                            if ($Setting->OperationCard == 0) {
                                continue;
                            }
                        }
                    }

                    $rate_body = $this->msClient->get("https://api.moysklad.ru/api/remap/1.2/entity/currency/")->rows;
                    $rate = null;
                    foreach ($rate_body as $item_rate){
                        if ($item_rate->name == "тенге" or $item_rate->fullName == "Казахстанский тенге"){
                            $rate =
                                ['meta'=> [
                                    'href' => $item_rate->meta->href,
                                    'metadataHref' => $item_rate->meta->metadataHref,
                                    'type' => $item_rate->meta->type,
                                    'mediaType' => $item_rate->meta->mediaType,
                                ],
                                ];
                        }
                    }

                    $body = [
                        'organization' => [  'meta' => [
                            'href' => $OldBody->organization->meta->href,
                            'type' => $OldBody->organization->meta->type,
                            'mediaType' => $OldBody->organization->meta->mediaType,
                        ] ],
                        'agent' => [ 'meta'=> [
                            'href' => $OldBody->agent->meta->href,
                            'type' => $OldBody->agent->meta->type,
                            'mediaType' => $OldBody->agent->meta->mediaType,
                        ] ],
                        'sum' => ($item['total']-$change) * 100,
                        'operations' => [
                            0 => [
                                'meta'=> [
                                    'href' => $OldBody->meta->href,
                                    'metadataHref' => $OldBody->meta->metadataHref,
                                    'type' => $OldBody->meta->type,
                                    'mediaType' => $OldBody->meta->mediaType,
                                    'uuidHref' => $OldBody->meta->uuidHref,
                                ],
                                'linkedSum' => 0
                            ], ],
                        'rate' => $rate
                    ];
                    if ($body['rate'] == null) unset($body['rate']);
                    $this->msClient->post($url_to_body, $body);
                }
                break;
            }
            default:{
               break;
            }
        }

    }

    private function createReturnDocument(mixed $newBody, mixed $putBody, mixed $oldBody, mixed $entity_type)
    {
        if ($entity_type != 'salesreturn') {
            $attributes_item =  $this->msClient->get('https://api.moysklad.ru/api/remap/1.2/entity/salesreturn/metadata/attributes/')->rows;
            $attributes = null;
            $positions = null;
            foreach ($attributes_item as $item){
                if ($item->name == 'фискальный номер (Nurkassa)'){
                    $attributes[] = [
                        'meta' => [
                            'href' => $item->meta->href,
                            'type' => $item->meta->type,
                            'mediaType' => $item->meta->mediaType,
                        ],
                        'value' => $putBody->data->params->receipt_number,
                    ];
                }
                if ($item->name == 'Ссылка для QR-кода (Nurkassa)'){
                    $attributes[] = [
                        'meta' => [
                            'href' => $item->meta->href,
                            'type' => $item->meta->type,
                            'mediaType' => $item->meta->mediaType,
                        ],
                        'value' => $putBody->data->params->qr_data,
                    ];
                }
                if ($item->name == 'Фискализация (Nurkassa)'){
                    $attributes[] = [
                        'meta' => [
                            'href' => $item->meta->href,
                            'type' => $item->meta->type,
                            'mediaType' => $item->meta->mediaType,
                        ],
                        'value' => true,
                    ];
                }

            }

            foreach ($oldBody['positions'] as $item) {
                unset($item['id']);
                $positions[] = $item;
            }

            $url = 'https://api.moysklad.ru/api/remap/1.2/entity/salesreturn';

            $body = [
                'organization' => [
                    'meta' => [
                        'href' => $newBody->organization->meta->href,
                        'metadataHref' => $newBody->organization->meta->metadataHref,
                        'type' => $newBody->organization->meta->type,
                        'mediaType' => $newBody->organization->meta->mediaType,
                    ]
                ],
                'agent' =>[
                    'meta' => [
                        'href' => $newBody->agent->meta->href,
                        'metadataHref' => $newBody->agent->meta->metadataHref,
                        'type' => $newBody->agent->meta->type,
                        'mediaType' => $newBody->agent->meta->mediaType,
                    ]
                ],
                'attributes' => $attributes,
                'positions' => $positions,
                'description' => 'Созданный документ возврата с ',
                'organizationAccount' => null,
                'demand' => null,
                'store' => null,
            ];

            if (isset($newBody->organizationAccount)){
                $body['organizationAccount'] = [
                    'meta' => [
                        'href' => $newBody->organizationAccount->meta->href,
                        'type' => $newBody->organizationAccount->meta->type,
                        'mediaType' => $newBody->organizationAccount->meta->mediaType,
                    ]
                ];
            } else { unlink($body['organizationAccount']); }

            if (isset($newBody->store)){
                $body['store'] = [
                    'meta' => [
                        'href' => $newBody->store->meta->href,
                        'metadataHref' => $newBody->store->meta->metadataHref,
                        'type' => $newBody->store->meta->type,
                        'mediaType' => $newBody->store->meta->mediaType,
                    ]
                ];
            } else { $store =  $this->msClient->get('https://api.moysklad.ru/api/remap/1.2/entity/store')->rows[0];
                $body['store'] = [
                    'meta' => [
                        'href' => $store->meta->href,
                        'metadataHref' => $store->meta->metadataHref,
                        'type' => $store->meta->type,
                        'mediaType' => $store->meta->mediaType,
                    ]
                ];
            }



            if ($entity_type == 'customerorder'){
                $body['description'] = $body['description'].'заказа покупателя, его номер:'. $newBody->name;
                unset($body['demand']);
            }
            if ($entity_type == 'demand'){
                $body['description'] = $body['description'].'отгрузка, его номер:'. $newBody->name;
                $body['demand'] = [
                    'meta' => [
                        'href' => $newBody->meta->href,
                        'metadataHref' => $newBody->meta->metadataHref,
                        'type' => $newBody->meta->type,
                        'mediaType' => $newBody->meta->mediaType,
                    ]
                ];
            }

            try {
                $this->msClient->post($url, $body);
            } catch (BadResponseException $exception){

            }
        }
    }

    private function descriptionToCreate(mixed $oldBody, mixed $postTicket, $message): string
    {
        $OldMessage = '';
        if (property_exists($oldBody, 'description')) {
            $OldMessage = $oldBody->description.PHP_EOL;
        }

        return (string) $OldMessage.'['.( (int) date('H') + 6 ).date(':i:s').' '. date('Y-m-d') .'] '. $message.$postTicket->data->params->receipt_number ;
    }

    private function codeUOM($UOM): \Illuminate\Http\JsonResponse|int|null
    {
        try {
           return $this->kassClient->unit($UOM);
        } catch (BadResponseException $e){
            return null;
        }
    }


}
