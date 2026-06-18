<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up()
{
    Schema::table('produk', function (Blueprint $table) { // Ubah 'produks' menjadi 'produk'
        $table->boolean('is_active')->default(true);
    });
}

public function down()
{
    Schema::table('produk', function (Blueprint $table) { // Ubah 'produks' menjadi 'produk'
        $table->dropColumn('is_active');
    });
}
};