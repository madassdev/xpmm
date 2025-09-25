<?php

namespace App\Services\Ledger;

use App\Models\{Account, Balance, Journal, Entry, IncomingPayment, User};
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class LedgerService
{
    /** Ensure a user wallet exists (and balance row). */
    public function userWallet(User $user, string $currency='NGN'): Account
    {
        $acc = Account::firstOrCreate(
            ['type'=>'USER_WALLET','owner_type'=>User::class,'owner_id'=>$user->id,'currency'=>$currency],
            ['status'=>'ACTIVE']
        );
        Balance::firstOrCreate(['account_id'=>$acc->id,'currency'=>$currency], ['available'=>0,'pending'=>0]);
        return $acc;
    }

    public function system(string $type, string $currency='NGN'): Account
    {
        $acc = Account::where(['type'=>$type,'currency'=>$currency])->firstOrFail();
        Balance::firstOrCreate(['account_id'=>$acc->id,'currency'=>$currency], ['available'=>0,'pending'=>0]);
        return $acc;
    }

    /** Post a provider credit (VA deposit) from IncomingPayment idempotently. */
    public function postDepositFromIncoming(IncomingPayment $ip, User $user): string
    {
        $jref = 'DEP:'.$ip->provider.':'.$ip->provider_ref;
        return $this->deposit($user, $ip->amount, $ip->currency, $jref, [
            'provider'=>$ip->provider,
            'provider_ref'=>$ip->provider_ref,
            'account_number'=>$ip->account_number,
            'payer_name'=>$ip->payer_name,
            'narration'=>$ip->narration,
        ]);
    }

    /** Generic deposit to user wallet (e.g., reconciled credit). */
    public function deposit(User $user, int $amount, string $currency, string $jref=null, array $meta=[]): string
    {
        $jref = $jref ?: 'DEP:'.Uuid::uuid4()->toString();

        DB::transaction(function () use ($user,$amount,$currency,$jref,$meta) {
            // Idempotency
            $existing = Journal::where('jref',$jref)->lockForUpdate()->first();
            if ($existing) return;

            $userAcc = $this->userWallet($user,$currency);
            $floatAcc= $this->system('PROVIDER_FLOAT',$currency);

            $userBal = $userAcc->balance()->lockForUpdate()->first();
            $floatBal= $floatAcc->balance()->lockForUpdate()->first();

            $j = Journal::create([
                'jref'=>$jref,'type'=>'DEPOSIT','currency'=>$currency,'amount'=>$amount,
                'state'=>Journal::POSTED,'meta'=>$meta,'posted_at'=>now()
            ]);

            Entry::create(['journal_id'=>$j->id,'account_id'=>$floatAcc->id,'direction'=>Entry::DEBIT,'amount'=>$amount]);
            Entry::create(['journal_id'=>$j->id,'account_id'=>$userAcc->id, 'direction'=>Entry::CREDIT,'amount'=>$amount]);

            $floatBal->available += $amount;
            $userBal->available  += $amount;
            $floatBal->save(); $userBal->save();
        });

        return $jref;
    }

    /** Purchase using wallet; credits provider float & fees. Throws on insufficient funds. */
    public function purchase(User $user, int $providerAmount, int $feeAmount, string $currency, string $jref=null, array $meta=[]): string
    {
        $jref = $jref ?: 'PUR:'.Uuid::uuid4()->toString();
        $total = $providerAmount + $feeAmount;

        DB::transaction(function () use ($user,$providerAmount,$feeAmount,$currency,$jref,$meta,$total) {
            if (Journal::where('jref',$jref)->lockForUpdate()->exists()) return;

            $userAcc  = $this->userWallet($user,$currency);
            $floatAcc = $this->system('PROVIDER_FLOAT',$currency);
            $feesAcc  = $this->system('FEES_REVENUE',$currency);

            $userBal  = $userAcc->balance()->lockForUpdate()->first();
            $floatBal = $floatAcc->balance()->lockForUpdate()->first();
            $feesBal  = $feesAcc->balance()->lockForUpdate()->first();

            if ($userBal->available < $total) {
                throw new \RuntimeException('INSUFFICIENT_FUNDS');
            }

            $j = Journal::create([
                'jref'=>$jref,'type'=>'BILL_PURCHASE','currency'=>$currency,'amount'=>$total,
                'state'=>Journal::POSTED,'meta'=>$meta,'posted_at'=>now()
            ]);

            // DR user total, CR provider + CR fees
            Entry::create(['journal_id'=>$j->id,'account_id'=>$userAcc->id,  'direction'=>Entry::DEBIT,'amount'=>$total]);
            Entry::create(['journal_id'=>$j->id,'account_id'=>$floatAcc->id, 'direction'=>Entry::CREDIT,'amount'=>$providerAmount]);
            if ($feeAmount > 0) {
                Entry::create(['journal_id'=>$j->id,'account_id'=>$feesAcc->id,  'direction'=>Entry::CREDIT,'amount'=>$feeAmount]);
            }

            $userBal->available  -= $total;
            $floatBal->available += $providerAmount;
            if ($feeAmount > 0) $feesBal->available += $feeAmount;

            $userBal->save(); $floatBal->save(); $feesBal->save();
        });

        return $jref;
    }

    /** Reverse a purchase (providerAmount + fee back to user). */
    public function reversePurchase(User $user, int $providerAmount, int $feeAmount, string $currency, string $originJref, array $meta=[]): string
    {
        $jref = 'REV:'.$originJref;

        DB::transaction(function () use ($user,$providerAmount,$feeAmount,$currency,$jref,$meta) {
            if (Journal::where('jref',$jref)->lockForUpdate()->exists()) return;

            $userAcc  = $this->userWallet($user,$currency);
            $floatAcc = $this->system('PROVIDER_FLOAT',$currency);
            $feesAcc  = $this->system('FEES_REVENUE',$currency);

            $userBal  = $userAcc->balance()->lockForUpdate()->first();
            $floatBal = $floatAcc->balance()->lockForUpdate()->first();
            $feesBal  = $feesAcc->balance()->lockForUpdate()->first();

            $j = Journal::create([
                'jref'=>$jref,'type'=>'REVERSAL','currency'=>$currency,'amount'=>$providerAmount+$feeAmount,
                'state'=>Journal::POSTED,'meta'=>$meta,'posted_at'=>now()
            ]);

            // Reverse: DR float, DR fees, CR user
            Entry::create(['journal_id'=>$j->id,'account_id'=>$floatAcc->id, 'direction'=>Entry::DEBIT,'amount'=>$providerAmount]);
            if ($feeAmount > 0) {
                Entry::create(['journal_id'=>$j->id,'account_id'=>$feesAcc->id,  'direction'=>Entry::DEBIT,'amount'=>$feeAmount]);
            }
            Entry::create(['journal_id'=>$j->id,'account_id'=>$userAcc->id,  'direction'=>Entry::CREDIT,'amount'=>$providerAmount+$feeAmount]);

            $floatBal->available -= $providerAmount;
            if ($feeAmount > 0) $feesBal->available -= $feeAmount;
            $userBal->available  += ($providerAmount+$feeAmount);

            $floatBal->save(); $feesBal->save(); $userBal->save();
        });

        return $jref;
    }
}
