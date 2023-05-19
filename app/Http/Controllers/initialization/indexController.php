<?php

namespace App\Http\Controllers\initialization;

use App\Http\Controllers\BD\getPersonal;
use App\Http\Controllers\Config\Lib\VendorApiController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class indexController extends Controller
{
    public function initialization(Request $request): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse
    {
        $contextKey = $request->contextKey;
        if ($contextKey == null) {
            return view("main.dump");
        }
        $vendorAPI = new VendorApiController();
        $employee = $vendorAPI->context($contextKey);
        $accountId = $employee->accountId;

        $isAdmin = $employee->permissions->admin->view;

        return to_route('main', [
            'accountId' => $accountId,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function index(Request $request, $accountId): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Foundation\Application
    {

        $isAdmin = $request->isAdmin;
       /* $getPersonal = new getPersonal($accountId);
        if ($getPersonal->status == "деактивированный" or $getPersonal->status == null){
            $hideOrShow = "show";
        } else  $hideOrShow = "hide";*/

        $hideOrShow = "hide";

        return view("main.index" , [
            'accountId' => $accountId,
            'isAdmin' => $isAdmin,
            'hideOrShow' => $hideOrShow,
        ] );

    }

    public function check(Request $request){

         $tmp = file_get_contents('AGOSTINA_f032b9441637d434620392928079e253669dc970.p12');
        $certificates = "";
        $cert = "";
        $pkey = "";
        $pass = "Aa1234";

        if (openssl_pkcs12_read($tmp,$certificates, $pass)){
            $index = 0;
            $Count = count(preg_split("/((\r?\n)|(\r\n?))/", $certificates['cert']));
            foreach(preg_split("/((\r?\n)|(\r\n?))/", $certificates['cert']) as $line){
                $index++;
                if ($index == 1 or ($index > $Count-2)) {

                }   else {
                    $cert = $cert.$line;
                }

            }

            $index = 0;
            $Count = count(preg_split("/((\r?\n)|(\r\n?))/", $certificates['pkey']));
            foreach(preg_split("/((\r?\n)|(\r\n?))/", $certificates['pkey']) as $line){
                $index++;
                if ($index == 1 or ($index > $Count-2)) {

                }   else {
                    $pkey = $pkey.$line;
                }

            }

            //dd( $data );

            dd($certificates, $cert, $pkey);
        } else {
            dd('no');
        }


    }

}
