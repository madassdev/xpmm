<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\ElectricityValidateRequest;
use App\Http\Requests\ElectricityPurchaseRequest;
use App\Services\Redbiller\ElectricityService;

class ElectricityController extends Controller
{
    public function __construct(private ElectricityService $svc) {}

    public function validateCustomer(ElectricityValidateRequest $r)
    {
        $res = $this->svc->validateMeter($r->validated());
        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    public function purchase(ElectricityPurchaseRequest $r)
    {
        $res = $this->svc->purchase($r->validated());
        return response()->json($res, $res['response']['ok'] ? 200 : 422);
    }

    public function status(string $reference)
    {
        $res = $this->svc->status($reference);
        return response()->json($res, $res['ok'] ? 200 : 422);
    }
}
