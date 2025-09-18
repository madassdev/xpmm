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
        Schema::create('electricity_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64);
            $table->unsignedInteger('units')->nullable(); // kWh or similar
            $table->string('tariff_code', 64)->nullable();
            $table->json('raw')->nullable(); // full fragment from provider
            $table->timestamps();
            $table->index(['token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_tokens');
    }
};
