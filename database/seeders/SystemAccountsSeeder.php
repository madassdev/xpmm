<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Balance;
use Illuminate\Database\Seeder;

class SystemAccountsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['type'=>'PROVIDER_FLOAT','currency'=>'NGN'],
            ['type'=>'FEES_REVENUE','currency'=>'NGN'],
            ['type'=>'HOLDS','currency'=>'NGN'],
            ['type'=>'ADJUSTMENTS','currency'=>'NGN'],
        ] as $row) {
            $acc = Account::firstOrCreate($row);
            Balance::firstOrCreate(['account_id'=>$acc->id,'currency'=>$row['currency']], ['available'=>0,'pending'=>0]);
        }
    }
}
