<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TokoCsvSeeder extends Seeder
{
    public function run()
    {
        // Sesuaikan nama file dengan yang ada di komputer Anda
        $file = database_path('seeders/Toko.csv'); 
        
        if (!file_exists($file)) {
            $this->command->error("File Toko.csv tidak ditemukan!");
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::table('toko')->truncate(); 

        $handle = fopen($file, "r");
        $header = true;
        $count = 0;
        
        // Daftar area dari React Anda untuk diacak
        $daftarArea = [
            "Surabaya Pusat", "Surabaya Timur", "Surabaya Barat", 
            "Surabaya Selatan", "Surabaya Utara", "Sidoarjo", "Gresik"
        ];

        while (($line = fgets($handle)) !== false) {
            // Bersihkan spasi dan tanda kutip
            $line = trim($line, " \t\n\r\0\x0B\"");
            
            // Lewati baris kosong
            if (empty($line)) continue;

            // Lewati baris pertama (Header)
            if ($header) { $header = false; continue; }

            // Karena hanya ada 1 data per baris, teks tersebut adalah Nama Toko
            $namaToko = $line;
            
            // Ambil 1 area secara acak dari array
            $areaAcak = $daftarArea[array_rand($daftarArea)];

            DB::table('toko')->insert([
                'nama_toko'        => $namaToko,
                'alamat'           => 'Alamat belum diatur', // Default string
                'area'             => $areaAcak,             // Diisi acak agar filter UI berfungsi
                'koordinat_lokasi' => '0,0',          
                'id_salesman'      => null,              
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $count++;
        }
        fclose($handle);
        Schema::enableForeignKeyConstraints();

        $this->command->info("BERHASIL! {$count} toko asli dari CSV telah disuntikkan dengan Area acak.");
    }
}