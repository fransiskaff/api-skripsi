<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
$this->call([
    UserSeeder::class,     
    TokoCsvSeeder::class,     // <--- Gunakan yang versi CSV ini
    ProdukSeeder::class,   
    PenjualanCsvSeeder::class 
]);
    }
}