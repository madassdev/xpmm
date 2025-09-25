<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->string('type', 32);                // USER_WALLET | PROVIDER_FLOAT | FEES_REVENUE | HOLDS | ADJUSTMENTS
            $t->nullableMorphs('owner');           // user wallets: owner_type=User, owner_id
            $t->char('currency', 4)->default('NGN');
            $t->string('status', 16)->default('ACTIVE');
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->unique(['type', 'owner_type', 'owner_id', 'currency'], 'accounts_unique_wallet');
            $t->index(['type', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
