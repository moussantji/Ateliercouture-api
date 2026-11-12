<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->after('price');
            $table->text('sizes')->nullable()->after('quantity');   // JSON array
            $table->text('colors')->nullable()->after('sizes');     // JSON array
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'sizes', 'colors']);
        });
    }
};
