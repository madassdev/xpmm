<?php

namespace App\Http\Controllers;

use App\Services\Redbiller\BillsService;
use App\Services\Redbiller\Client;
use Illuminate\Http\Request;

class BillsController extends Controller
{
    public function indexx()
    {
        $data = [
            "product" => "DStv",
            "code" => "3800",
            "smart_card_no" => "0000000000",
            "customer_name" => "JOHN DOE",
            "phone_no" => "08144698943",
            "callback_url" => "https://domain.com",
            "reference" => "TRalsGTyew01i"
        ];

        $client = new Client([]);
        $bills = new BillsService($client);
        $data = $bills->cablePurchaseCreate($data);
        return $data;
        return inertia()->render('Bills/Index');
    }
    public function index()
    {
        return inertia()->render('Bills/Index');
    }
}
