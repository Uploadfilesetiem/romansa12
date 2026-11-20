<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Menyimpan QRIS statis milik toko (dari bank/GoPay/DANA/dll) di baris
// stok_master yang sama, supaya nominal bisa disisipkan otomatis (jadi
// QRIS dinamis) setiap kali pelanggan bayar QRIS. Semua diproses di HP
// sendiri, tanpa payment gateway ataupun internet.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stok_master', function (Blueprint $table) {
            $table->text('qris_statis')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stok_master', function (Blueprint $table) {
            $table->dropColumn('qris_statis');
        });
    }
};
