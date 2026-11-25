<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('card_last_four', 4)->nullable();
        });

        DB::table('payments')->orderBy('id')->each(function ($payment) {
            $digits = preg_replace('/\D/', '', (string) $payment->card_number);

            DB::table('payments')
                ->where('id', $payment->id)
                ->update(['card_last_four' => substr($digits, -4) ?: null]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['card_number', 'ccv']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('card_number')->nullable();
            $table->integer('ccv')->nullable();
            $table->dropColumn('card_last_four');
        });
    }
};
