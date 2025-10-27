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
        Schema::create('giftcard_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('giftcard_id')->constrained()->onDelete('cascade');
            
            $table->string('type', 16); // 'buy' | 'sell'
            $table->string('currency', 8); // 'NGN', 'USD', 'EUR'
            $table->string('card_type', 16)->nullable(); // 'e-card' | 'physical'
            
            $table->unsignedBigInteger('amount'); // Amount in cents/kobo
            $table->unsignedInteger('quantity')->default(1);
            
            $table->string('payment_method', 16)->nullable(); // 'NGN', 'BTC', 'USDT'
            $table->string('status', 24)->default('pending'); // 'pending', 'processing', 'completed', 'rejected'
            
            $table->json('images')->nullable(); // Array of image paths
            $table->text('notes')->nullable();
            
            $table->unsignedBigInteger('amount_received')->nullable(); // Amount received in payment currency
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['giftcard_id', 'type']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giftcard_transactions');
    }
};
