<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bill_transactions', function (Blueprint $table) {
            $table->id();

            // Your public reference for idempotency + lookups
            $table->string('reference', 64)->unique();

            // Service taxonomy
            $table->string('service', 32);            // airtime|data|airtime_pin|electricity|tv|...
            $table->string('product', 32)->nullable(); // maps to Redbiller "product" (MTN|Airtel|...)
            $table->string('network', 32)->nullable(); // optional local alias (kept for FE compatibility)

            // Target account details
            $table->string('phone', 32)->nullable();   // MSISDN (maps to phone_no)
            $table->string('account', 64)->nullable(); // meter/smartcard/customer id for non-mobile bills
            $table->string('plan_id', 64)->nullable(); // data plan code/category if applicable
            $table->boolean('ported')->nullable();     // true if number ported (optional)

            // Money (store base unit to avoid float bugs; kobo/naira-cents)
            $table->unsignedBigInteger('amount')->default(0);            // requested
            $table->unsignedBigInteger('amount_paid')->default(0);       // provider-reported
            $table->unsignedBigInteger('amount_discount')->default(0);   // if any

            // Provider fields
            $table->string('provider', 32)->default('redbiller');
            $table->string('provider_txn_id', 128)->nullable();
            $table->string('callback_url', 255)->nullable();

            // Status lifecycle
            $table->string('status', 24)->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Raw payloads for audit/debug
            $table->json('request_payload')->nullable();
            $table->json('provider_response')->nullable();
            $table->json('webhook_payload')->nullable();

            $table->timestamps();

            // Hot paths
            $table->index(['service', 'status']);
            $table->index(['phone']);
            $table->index(['account']);
            $table->index(['provider', 'provider_txn_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_transactions');
    }
};
