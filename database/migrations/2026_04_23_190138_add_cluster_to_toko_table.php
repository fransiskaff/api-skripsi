<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('toko', function (Blueprint $table) {
            // Menambahkan kolom 'cluster' setelah kolom 'nama_toko' (atau sesuaikan dengan nama kolom Anda)
            // nullable() artinya boleh kosong, karena toko baru belum punya klaster
            $table->string('cluster')->nullable()->after('id'); 
        });
    }

    public function down()
    {
        Schema::table('toko', function (Blueprint $table) {
            // Menghapus kolom jika migration di-rollback
            $table->dropColumn('cluster');
        });
    }
};