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
        Schema::create('entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $t->foreignId('account_id')->constrained()->cascadeOnDelete();
            $t->string('direction', 6);           // DEBIT | CREDIT
            $t->unsignedBigInteger('amount');
            $t->timestamps();
            $t->index(['journal_id']);
            $t->index(['account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
