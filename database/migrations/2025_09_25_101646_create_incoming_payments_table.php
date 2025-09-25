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
        Schema::create('incoming_payments', function (Blueprint $t) {
            $t->id();
            $t->string('provider', 32)->default('redbiller');
            $t->string('provider_ref', 128);
            $t->string('account_number', 20)->index();
            $t->unsignedBigInteger('amount');
            $t->char('currency', 3)->default('NGN');
            $t->string('payer_name', 128)->nullable();
            $t->string('narration', 255)->nullable();
            $t->date('value_date')->nullable();
            $t->string('status', 16)->default('RECEIVED'); // RECEIVED|POSTED|IGNORED
            $t->json('raw')->nullable();
            $t->timestamps();
            $t->unique(['provider', 'provider_ref']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_payments');
    }
};
