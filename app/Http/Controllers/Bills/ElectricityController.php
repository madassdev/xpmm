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
        $provider = $res['response'] ?? [];
        $transaction = $res['transaction'] ?? [];

        return response()->json([
            'reference'      => $res['reference'],
            'status'         => $transaction['status'] ?? null,
            'customer_name'  => $transaction['customer_name'] ?? null,
            'amount'         => $transaction['amount'] ?? null,
            'amount_paid'    => $transaction['amount_paid'] ?? null,
            'fee'            => $transaction['fee'] ?? null,
            'cost'           => $transaction['cost'] ?? null,
            'currency'       => $transaction['currency'] ?? null,
            'tokens'         => $transaction['tokens'] ?? [],
            'provider'       => [
                'ok'     => $provider['ok'] ?? false,
                'status' => $provider['status'] ?? null,
            ],
        ], ($provider['ok'] ?? false) ? 200 : 422);
    }

    public function status(string $reference)
    {
        $res = $this->svc->status($reference);
        return response()->json($res, $res['ok'] ? 200 : 422);
    }
}
