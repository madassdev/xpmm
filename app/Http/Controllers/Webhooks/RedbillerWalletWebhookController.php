<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\{IncomingPayment, VirtualAccount, User};
use App\Services\Ledger\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedbillerWalletWebhookController extends Controller
{
    public function __construct(private LedgerService $ledger) {}

    public function handle(Request $r)
    {
        // TODO: verify HMAC/signature if provider supplies (recommended!)
        $payload = $r->all();
        Log::info('redbiller.wallet.webhook', $payload);

        $providerRef = $payload['reference'] ?? $payload['provider_ref'] ?? null;
        $acct        = $payload['account_number'] ?? null;
        $amount      = (int) ($payload['amount'] ?? 0);
        $currency    = $payload['currency'] ?? 'NGN';
        $payer       = $payload['payer_name'] ?? null;
        $narration   = $payload['narration'] ?? null;

        if (!$providerRef || !$acct || $amount <= 0) {
            return response()->json(['ok'=>false,'msg'=>'invalid'], 400);
        }

        // De-dupe
        $ip = IncomingPayment::firstOrCreate(
            ['provider'=>'redbiller','provider_ref'=>$providerRef],
            [
                'account_number'=>$acct,'amount'=>$amount,'currency'=>$currency,
                'payer_name'=>$payer,'narration'=>$narration,'status'=>'RECEIVED','raw'=>$payload
            ]
        );

        // Map account number → user
        $va = VirtualAccount::where('provider','redbiller')->where('account_number',$acct)->first();
        if (!$va) {
            Log::warning('webhook.va_not_found', ['acct'=>$acct]);
            return response()->json(['ok'=>true]); // acknowledge to avoid retries; manual recon handles it
        }

        $user = User::find($va->user_id);
        if (!$user) return response()->json(['ok'=>true]);

        // Post deposit (idempotent by provider_ref)
        $this->ledger->postDepositFromIncoming($ip, $user);
        $ip->update(['status'=>'POSTED']);

        return response()->json(['ok'=>true]);
    }
}
