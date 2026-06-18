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
        Schema::create('rfm_clusters', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_toko')->constrained('toko')->onDelete('cascade');
        $table->float('recency');
        $table->float('frequency');
        $table->float('monetary');
        $table->integer('cluster_id')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfm_clusters');
    }
};
