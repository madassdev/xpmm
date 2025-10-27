<?php

namespace App\Http\Controllers\Admin;

use App\Events\WalletFunded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ManualWalletFundingRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class WalletFundingController extends Controller
{
    /**
     * Display a listing of users and wallet balances.
     */
    public function index(): View
    {
        $this->authorize('fund', Wallet::class);

        $users = User::query()
            ->with('wallet')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.wallets.fund', [
            'users' => $users,
        ]);
    }

    /**
     * Show the funding form.
     */
    public function create(): View
    {
        return $this->index();
    }

    /**
     * Handle manual wallet funding.
     */
    public function store(ManualWalletFundingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $admin = $request->user();

        $transaction = DB::transaction(function () use ($data, $admin): WalletTransaction {
            $wallet = Wallet::query()
                ->where('user_id', $data['user_id'])
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = new Wallet([
                    'user_id' => $data['user_id'],
                    'currency' => $data['currency'] ?? 'NGN',
                    'balance' => 0,
                ]);

                $wallet->save();
            }

            $amount = (float) $data['amount'];
            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = round($balanceBefore + $amount, 2);

            $wallet->forceFill([
                'balance' => number_format($balanceAfter, 2, '.', ''),
            ])->save();

            return $wallet->transactions()->create([
                'user_id' => $wallet->user_id,
                'processed_by' => $admin?->getKey(),
                'type' => WalletTransaction::TYPE_CREDIT,
                'amount' => number_format($amount, 2, '.', ''),
                'balance_before' => number_format($balanceBefore, 2, '.', ''),
                'balance_after' => number_format($balanceAfter, 2, '.', ''),
                'reference' => $data['reference'],
                'description' => $data['description'] ?? null,
                'metadata' => [
                    'source' => 'manual_admin_credit',
                ],
            ]);
        });

        event(new WalletFunded($transaction));

        return redirect()
            ->route('admin.wallets.fund')
            ->with('status', 'Wallet funded successfully.');
    }
}
