<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salesman_id')->constrained('users')->onDelete('cascade');
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->integer('month');
            $table->integer('year');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('sales_targets');
    }
};