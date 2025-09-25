<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Services\Redbiller\VAService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private LedgerService $ledger, private VAService $va) {}

    public function balance(Request $r)
    {
        /** @var User $user */
        $user = $r->user();
        $acc = $this->ledger->userWallet($user,'NGN');
        $bal = $acc->balance;
        return response()->json([
            'currency'=>'NGN',
            'available' => $bal->available,
            'pending'   => $bal->pending,
        ]);
    }

    public function virtualAccount(Request $r)
    {
        /** @var User $user */
        $user = $r->user();
        $va = \App\Models\VirtualAccount::where('user_id',$user->id)->first();
        if (!$va) $va = $this->va->getOrCreateVA($user);
        return response()->json([
            'account_number'=>$va->account_number,
            'bank_name'=>$va->bank_name,
            'provider'=>$va->provider,
        ]);
    }
}
