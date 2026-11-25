<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('favorite_products', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('product_ratings', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('favorite_products', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
        });

        Schema::table('product_ratings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
        });
    }
};
