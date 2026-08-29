<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catatan bebas per item (mis. nama selai/topping spesifik yang dipakai),
// supaya kasir bisa mencatat detail yang tidak tertampung di nama menu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            $table->string('catatan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_item', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }
};
