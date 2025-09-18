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
        Schema::table('bill_transactions', function (Blueprint $table) {
            $table->string('customer_name', 120)->nullable()->after('account');
            $table->unsignedBigInteger('fee')->default(0)->after('amount_discount');
            $table->unsignedBigInteger('cost')->default(0)->after('fee');
            $table->char('currency', 3)->default('NGN')->after('cost');
            $table->json('meta')->nullable()->after('webhook_payload');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_transactions', function (Blueprint $table) {
            $table->dropColumn(['customer_name','fee','cost','currency','meta']);
        });
    }
};
