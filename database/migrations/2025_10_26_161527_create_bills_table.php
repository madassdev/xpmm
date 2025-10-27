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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            
            // Service type (data, cable, electricity, etc.)
            $table->string('service', 32);
            
            // Provider/Product (MTN, Airtel, DStv, etc.)
            $table->string('provider', 64);
            
            // Plan code from provider API
            $table->string('code', 128);
            
            // Display name
            $table->string('name', 255);
            
            // Price in kobo/cents (integer to avoid float issues)
            $table->unsignedBigInteger('price')->default(0);
            
            // Additional metadata from provider (JSON)
            $table->json('meta')->nullable();
            
            // When last synced from API
            $table->timestamp('synced_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for fast lookups
            $table->unique(['service', 'provider', 'code'], 'bills_unique_plan');
            $table->index(['service', 'provider']);
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
