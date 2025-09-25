<?php

namespace App\Http\Controllers\Bills;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataPurchaseRequest;
use App\Services\Redbiller\BillsService;
use App\Support\NetworkMap;
use App\Models\BillTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MobileDataController extends Controller
{
    public function __construct(private BillsService $bills) {}

    public function getPlans(Request $r)
    {
        $r->validate([
            'network' => 'required|string|in:mtn,airtel,glo,9mobile',
        ]);

        $network = strtolower($r->query('network'));
        $product = NetworkMap::toProduct($network); // e.g. "MTN"
        try {
            $plans = $this->bills->getDataPlansList($product);
        } catch (Exception $err) {
            return response()->json([
                'ok' => false,
                'message' => $err->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok'        => true,
            'network'   => $network,
            'product'   => $product,
            'plans'     => $plans,
            'raw'       => ['count' => count($plans)], // small hint; not leaking the whole payload
        ]);
    }
    public function purchase(DataPurchaseRequest $r)
    {
        // 1) Validated FE payload
        $data = $r->validated();
        $code = $data['planId'];
        $provider = NetworkMap::toProduct($data['provider']);

        $reference = $data['reference'] ?? Str::ulid()->toBase32();

        $redbillerPayload = [
            'product'   => $provider,
            'phone_no'  => $data['phone'],
            'code'    => $code,
            'reference' => $reference,
        ];

        if (array_key_exists('ported', $data)) {
            // Redbiller samples often show string "true"/"false"
            $redbillerPayload['ported'] = $data['ported'] ? 'true' : 'false';
        }
        if (!empty($data['callback_url'])) {
            $redbillerPayload['callback_url'] = $data['callback_url'];
        }

        $plan = $this->bills->getPlanByCode($provider, $code);
        $bundle = $plan['name'];
        $amount = $plan['price'];
        $product = "{$provider} - $bundle";
        dd($product);
        $tx = BillTransaction::create([
            'reference'         => $reference,
            'service'           => 'data',
            'product'           => $product,
            'network'           => $data['provider'],
            'phone'             => $data['phone'],
            'amount'            => (int) $amount,
            'provider'          => 'redbiller',
            'status'            => BillTransaction::S_PENDING,
            'request_payload'   => $redbillerPayload, // what we’ll send out
            'meta'              => [
                'asset' => $data['asset'] ?? 'NGN',
                'pin_hash' => Hash::make($data['pin']), // never log or return
            ],
        ]);

        // 4) Call provider via BillsService (uses the redbiller payload)
        $res = $this->bills->dataPurchaseCreate([
            // support both variants inside service; we pass canonical keys
            'product'      => $redbillerPayload['product'],
            'phone_no'     => $redbillerPayload['phone_no'],
            'amount'       => $redbillerPayload['amount'],
            'reference'    => $reference,
            'ported'       => $redbillerPayload['ported'] ?? null,
            'callback_url' => $redbillerPayload['callback_url'] ?? null,
            'plan' => $bundle,
            // nothing else; asset/pin stay internal
        ]);

        // 5) Update local txn from provider response
        $ok = $res['response']['ok'] ?? false;
        $body = $res['response']['json'] ?? ['raw' => $res['response']['body'] ?? null];

        $tx->update([
            'status'            => $ok ? strtoupper($body['status'] ?? 'PENDING') : BillTransaction::S_FAILED,
            'provider_txn_id'   => $body['id'] ?? $tx->provider_txn_id,
            'provider_response' => $body,
        ]);

        // 6) Return safe response (never return pin or pin_hash)
        return response()->json([
            'reference' => $reference,
            'status'    => $tx->status,
            'provider'  => [
                'ok'     => $ok,
                'status' => $res['response']['status'] ?? null,
            ],
        ], $ok ? 200 : 422);
    }
}
