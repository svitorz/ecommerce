<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders_products', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders_products', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
