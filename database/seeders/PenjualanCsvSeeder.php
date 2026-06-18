<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PenjualanCsvSeeder extends Seeder
{
    public function run()
    {
        $filePath = database_path('seeders/data_skripsi_hari_kerja.csv'); 
        
        if (!file_exists($filePath)) {
            $this->command->error("File data_skripsi_hari_kerja.csv tidak ditemukan.");
            return;
        }

        // 1. Optimasi Database & Reset Tabel
        DB::disableQueryLog();
        Schema::disableForeignKeyConstraints();

        // Kosongkan tabel transaksi agar bersih saat diulang
        DB::table('detail_penjualan')->truncate();
        DB::table('penjualan')->truncate();

        // 2. Cache Master Data (LOWERCASE agar kebal salah ketik/spasi)
        $tokoCache = [];
        foreach (DB::table('toko')->pluck('id', 'nama_toko') as $nama => $id) {
            $tokoCache[strtolower(trim($nama))] = $id;
        }

        $produkCache = [];
        foreach (DB::table('produk')->pluck('id', 'nama_produk') as $nama => $id) {
            $produkCache[strtolower(trim($nama))] = $id;
        }
        
        $file = fopen($filePath, 'r');
        
        // Deteksi delimiter
        $firstLine = fgets($file);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($file); 
        
        $isHeader = true;
        $detailBatch = [];
        $batchSize = 1000;

        DB::beginTransaction();

        try {
            while (($data = fgetcsv($file, 2000, $delimiter)) !== FALSE) {
                if ($isHeader) { 
                    $isHeader = false;
                    continue; 
                }

                // Format CSV: Tanggal, Nama_Toko, Nama_Produk, Qty, Harga_Satuan, Total_Harga
                if (count($data) >= 6 && !empty($data[0])) {
                    
                    // BERSIHKAN DATA MENTAH
                    $tanggal = trim($data[0], " \t\n\r\0\x0B\"");
                    $namaTokoOriginal = trim($data[1], " \t\n\r\0\x0B\"");
                    $namaProdukOriginal = trim($data[2], " \t\n\r\0\x0B\"");
                    
                    // BUAT KUNCI PENCARIAN (Huruf Kecil)
                    $namaTokoKey = strtolower($namaTokoOriginal);
                    $namaProdukKey = strtolower($namaProdukOriginal);
                    
                    $qty = (int) preg_replace('/[^0-9]/', '', $data[3]);
                    $hargaSatuan = (int) preg_replace('/[^0-9]/', '', $data[4]);
                    $totalHarga = (int) preg_replace('/[^0-9]/', '', $data[5]);
                    
                    // --- AUTO-REGISTER TOKO ---
                    if (!isset($tokoCache[$namaTokoKey])) {
                        $newTokoId = DB::table('toko')->insertGetId([
                            'nama_toko' => $namaTokoOriginal,
                            'alamat' => 'Alamat Otomatis',
                            'area' => 'Belum Ditentukan', 
                            'koordinat_lokasi' => '0,0',
                            'id_salesman' => null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $tokoCache[$namaTokoKey] = $newTokoId;
                    }
                    $idToko = $tokoCache[$namaTokoKey];

                    // --- AUTO-REGISTER PRODUK ---
                    if (!isset($produkCache[$namaProdukKey])) {
                        $newProdukId = DB::table('produk')->insertGetId([
                            'sku' => 'AUTO-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                            'nama_produk' => $namaProdukOriginal,
                            'harga_jual' => $hargaSatuan > 0 ? $hargaSatuan : 1000,
                            'isi_per_kardus' => 1,
                            'stok_fisik' => 1000, // Beri stok awal
                            'safety_stock' => 10,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $produkCache[$namaProdukKey] = $newProdukId;
                    }
                    $idProduk = $produkCache[$namaProdukKey];

                    // 3. Masukkan ke Tabel Induk (penjualan)
                    try {
                        $formattedDate = Carbon::createFromFormat('d/m/Y', $tanggal)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $formattedDate = now()->format('Y-m-d'); // Fallback jika format tanggal CSV aneh
                    }

                    $idPenjualan = DB::table('penjualan')->insertGetId([
                        'tanggal_transaksi' => $formattedDate,
                        'id_toko' => $idToko,
                        'total_harga' => $totalHarga,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // 4. Tampung Tabel Anak (detail_penjualan)
                    $detailBatch[] = [
                        'id_penjualan' => $idPenjualan,
                        'id_produk' => $idProduk,
                        'qty' => $qty,
                        'harga_satuan' => $hargaSatuan,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // 5. Eksekusi Bulk Insert per 1000 baris
                    if (count($detailBatch) >= $batchSize) {
                        DB::table('detail_penjualan')->insert($detailBatch);
                        $detailBatch = []; 
                    }
                }
            }

            // Sisa baris yang belum di-insert
            if (count($detailBatch) > 0) {
                DB::table('detail_penjualan')->insert($detailBatch);
            }

            DB::commit();
            $this->command->info('Sukses! Data Riwayat Penjualan beserta variasi produknya telah berhasil disuntikkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Proses terhenti: " . $e->getMessage());
        }

        fclose($file);
        Schema::enableForeignKeyConstraints();
    }
}