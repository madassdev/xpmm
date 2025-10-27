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
        Schema::create('crypto_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 20);
            $table->decimal('rate', 18, 8);
            $table->string('wallet_address', 255);
            $table->string('sale_type', 10);
            $table->decimal('charges', 7, 4)->default(0);
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_assets');
    }
};

