<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the promotion rules already consumed by the mobile application.
     * Existing codes remain compatible: percentage is the historical default.
     */
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->string('type', 20)->default('percentage')->after('discount');
            $table->unsignedInteger('min_amount')->default(0)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->dropColumn(['type', 'min_amount']);
        });
    }
};
