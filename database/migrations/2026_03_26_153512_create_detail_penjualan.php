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
        Schema::create('detail_penjualan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_penjualan')->constrained('penjualan')->onDelete('cascade');
        $table->foreignId('id_produk')->constrained('produk')->onDelete('cascade');
        $table->integer('qty');
        $table->integer('harga_satuan');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_penjualan');
    }
};
