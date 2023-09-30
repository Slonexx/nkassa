<?php

namespace App\Http\Controllers\Config\Lib;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class cfg extends Controller
{
    public $appId;
    public $appUid;
    public $secretKey;
    public $appBaseUrl;
    public $moyskladVendorApiEndpointUrl;
    public $moyskladJsonApiEndpointUrl;


    public function __construct()
    {
        $this->appId = '0d1c2b73-287d-4598-b406-550df40b1b35';
        $this->appUid = 'nurkassa.smartinnovations';
        $this->secretKey = "X5Fe3VMGJQhbUpihfMlrY64dTPEwsxukXR3D3ykcnw9P3qerXE5CgOtwIChhKuMSTxwoNoSBR2ZQitRYjBohZYxfJjL1JnvMQ6AylchGzUBg6xhUSSjrD1riv5YMkDsJ";
        $this->appBaseUrl = 'https://smartnurkassa.kz/';
        $this->moyskladVendorApiEndpointUrl = 'https://apps-api.moysklad.ru/api/vendor/1.0';
        $this->moyskladJsonApiEndpointUrl = 'https://api.moysklad.ru/api/remap/1.2';
    }


}
