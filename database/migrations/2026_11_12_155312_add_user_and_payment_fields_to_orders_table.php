<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable()->after('promo_code');
            $table->string('delivery_method')->nullable()->after('payment_method');
            $table->text('delivery_address')->nullable()->after('delivery_method');
            $table->timestamp('paid_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'payment_method', 'delivery_method', 'delivery_address', 'paid_at']);
        });
    }
};
