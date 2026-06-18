<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('produk', function (Blueprint $table) {
        $table->integer('isi_per_kardus')->default(1)->after('safety_stock');
    });
}

public function down()
{
    Schema::table('produk', function (Blueprint $table) {
        $table->dropColumn('isi_per_kardus');
    });
}

    /**
     * Reverse the migrations.
     */

};
