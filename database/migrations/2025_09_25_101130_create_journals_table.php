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
        Schema::create('journals', function (Blueprint $t) {
            $t->id();
            $t->uuid('jref')->unique();           // idempotency key per business op
            $t->string('type', 32);               // DEPOSIT | BILL_PURCHASE | WITHDRAWAL | REVERSAL | ADJUSTMENT | HOLD | CAPTURE | VOID
            $t->char('currency', 4)->default('NGN');
            $t->unsignedBigInteger('amount');     // headline amount for the op
            $t->string('state', 16)->default('POSTED'); // PENDING|POSTED|SETTLED|REVERSED
            $t->json('meta')->nullable();         // reference links (provider_ref, bill_reference, etc.)
            $t->timestamp('posted_at')->nullable();
            $t->timestamps();
            $t->index(['type', 'state', 'currency']);
            $t->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
