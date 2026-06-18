<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProdukSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('produk')->truncate();
        Schema::enableForeignKeyConstraints();

        $dataProduk = [
            ['sku' => 'A402', 'nama_produk' => 'AIM Roasted Corn 35g', 'harga_jual' => 2500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A602', 'nama_produk' => 'AIM Crispy Crackers Mini 30g', 'harga_jual' => 2000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K205', 'nama_produk' => 'Kangguru Bunga Gem 90g', 'harga_jual' => 5500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'S124', 'nama_produk' => 'Aneka Gabin Oat SP12(220g)', 'harga_jual' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO126', 'nama_produk' => 'Bogabis Crispy Lemon 200g', 'harga_jual' => 15000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K901', 'nama_produk' => 'Kangguru marie kecil chocolate 30 gr', 'harga_jual' => 1550, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K900', 'nama_produk' => 'kangguru Marie kecil 30 gr', 'harga_jual' => 1550, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'M300', 'nama_produk' => 'Masterbis Gabin master 120 gr', 'harga_jual' => 5500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A300', 'nama_produk' => 'AIM Roasted corn 80 gr', 'harga_jual' => 5000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A303', 'nama_produk' => 'AIM Toasty Cheese 80 gr', 'harga_jual' => 5000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A265 R', 'nama_produk' => 'AIM lafero chocolate Wafer pack 70', 'harga_jual' => 6000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A243', 'nama_produk' => 'AIM Roasted corn 180 gr', 'harga_jual' => 11500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO800', 'nama_produk' => 'Bogabis Square Puff duo 80P', 'harga_jual' => 1200, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO 244', 'nama_produk' => 'Bogabis Gabin Army Butter 125 gr', 'harga_jual' => 6000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K905', 'nama_produk' => 'Kangguru Durian Biscuits 30gr', 'harga_jual' => 1600, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A280', 'nama_produk' => 'AIM Crispy Crackers 150 gr', 'harga_jual' => 8500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A365', 'nama_produk' => 'AIM Gabin Chocolate 120 gr', 'harga_jual' => 5500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K123', 'nama_produk' => 'Kangguru Wafer Chocolate 280 gr', 'harga_jual' => 12000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A251', 'nama_produk' => 'AIM Aneka Cream Crackers 300 gr', 'harga_jual' => 11000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A247', 'nama_produk' => 'AIM Aneka Square Puff 300 gr', 'harga_jual' => 11000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A245', 'nama_produk' => 'AIM Toasty Cheese 180 gr', 'harga_jual' => 11500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A213', 'nama_produk' => 'AIM Marie susu 100 gr', 'harga_jual' => 5000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K361', 'nama_produk' => 'Kangguru Rose Cream 100 gr', 'harga_jual' => 4000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A202', 'nama_produk' => 'AIM vegetable Crackers 180 gr', 'harga_jual' => 12000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K362', 'nama_produk' => 'Kangguru Rose chocolate 100 gr', 'harga_jual' => 4000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K253', 'nama_produk' => 'kangguru kacang crackers 150 gr', 'harga_jual' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'S121', 'nama_produk' => 'Anekabis Gabin Oat 350 gr', 'harga_jual' => 29000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'S120', 'nama_produk' => 'Anekabis Gabin Super 350 gr', 'harga_jual' => 29000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A308', 'nama_produk' => 'AIM Mini Assorted Biscuits 150 gr', 'harga_jual' => 7500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BB243', 'nama_produk' => 'BNB Cream Crackers 300 gr', 'harga_jual' => 11000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A242', 'nama_produk' => 'AIM Roasted Chicken 180 gr', 'harga_jual' => 11500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K363', 'nama_produk' => 'kangguru Butter coconut 100 gr', 'harga_jual' => 4000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO 242', 'nama_produk' => 'Bogabis Malkist Renyah 100 gr', 'harga_jual' => 5000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO 202', 'nama_produk' => 'Bogabis Chocolate Cream 180 gr', 'harga_jual' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BB180', 'nama_produk' => 'BNB gabin Traditional 210 gr', 'harga_jual' => 9000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO204', 'nama_produk' => 'Bogabis Durian Cream 180 gr', 'harga_jual' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO243', 'nama_produk' => 'Bogabis gabin Army Kelapa 125 gr', 'harga_jual' => 6000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO203', 'nama_produk' => 'Bogabis Peanut Cream 180 gr', 'harga_jual' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'S122', 'nama_produk' => 'Anekabis Gabin Coklat 350 gr', 'harga_jual' => 29000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K364', 'nama_produk' => 'Kangguru Durian Biscuits 100 gr', 'harga_jual' => 4000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'BO201', 'nama_produk' => 'Bogabis Bonbon 180 gr', 'harga_jual' => 8000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K128', 'nama_produk' => 'Kangguru Wafer Vanilla + susu 280 gr', 'harga_jual' => 12000, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'A283', 'nama_produk' => 'AIM Crispy Lemon 150 gr', 'harga_jual' => 8500, 'created_at' => now(), 'updated_at' => now()],
            ['sku' => 'K126', 'nama_produk' => 'Kangguru Wafer Strawberry 280 gr', 'harga_jual' => 12000, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('produk')->insert($dataProduk);

        $this->command->info('Sukses! Master Data Produk beserta SKU berhasil dimasukkan.');
    }
}