<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HitungKMeans extends Command
{
    protected $signature = 'sistem:hitung-kmeans';
    protected $description = 'Menjalankan K-Means interaktif dengan Elbow Method';

    public function handle()
    {
        $this->info('1. Mengekstrak dan Membangun Matriks Data...');

        // Ambil semua ID Produk sebagai kolom
        $produkIds = DB::table('produk')->pluck('id')->toArray();
        if (empty($produkIds)) {
            $this->error('Data produk kosong!');
            return;
        }

        // Ambil semua ID Toko sebagai baris
        $tokoIds = DB::table('toko')->pluck('id')->toArray();
        if (empty($tokoIds)) {
            $this->error('Data toko kosong!');
            return;
        }

        // Siapkan Matriks [Toko => [Produk1 => 0, Produk2 => 0, ...]]
        $dataset = [];
        foreach ($tokoIds as $idToko) {
            $dataset[$idToko] = array_fill_keys($produkIds, 0);
        }

        // Isi Matriks dengan Qty pembelian dari detail_penjualan
        // Sesuaikan nama tabel 'detail_penjualan' dan kolom 'qty' dengan database Anda
        $transaksi = DB::table('penjualan')
            ->join('detail_penjualan', 'penjualan.id', '=', 'detail_penjualan.id_penjualan')
            ->select('penjualan.id_toko', 'detail_penjualan.id_produk', DB::raw('SUM(detail_penjualan.qty) as total_qty'))
            ->groupBy('penjualan.id_toko', 'detail_penjualan.id_produk')
            ->get();

        foreach ($transaksi as $row) {
            if (isset($dataset[$row->id_toko][$row->id_produk])) {
                $dataset[$row->id_toko][$row->id_produk] = (int) $row->total_qty;
            }
        }

        $this->info('Matriks berhasil dibangun. Mengkalkulasi Elbow Method (K=1 sampai K=10)...');

        // 2. MENGHITUNG ELBOW METHOD (WCSS)
        $wcssValues = [];
        $maxK = min(10, count($dataset)); // Uji sampai K=10 atau batas jumlah toko

        for ($k = 1; $k <= $maxK; $k++) {
            $hasil = $this->jalankanKMeansN_Dimensi($dataset, $k);
            $wcssValues[] = [
                'K' => $k,
                'WCSS (Inertia)' => round($hasil['wcss'], 2)
            ];
        }

        // Tampilkan tabel ke terminal
        $this->table(['Nilai K (Jumlah Klaster)', 'Nilai WCSS (Semakin kecil semakin baik)'], $wcssValues);
        
        $this->info('Tips: Pilih nilai K di mana penurunan WCSS mulai melandai (membentuk siku/elbow).');

        // 3. INTERAKSI USER UNTUK MEMILIH K TERBAIK
        $kTerbaik = $this->ask('Berdasarkan tabel di atas, masukkan nilai K terbaik yang ingin digunakan:');

        if (!is_numeric($kTerbaik) || $kTerbaik < 1 || $kTerbaik > $maxK) {
            $this->error('Nilai K tidak valid. Membatalkan proses.');
            return;
        }

        $this->info("Menjalankan K-Means final dengan K = {$kTerbaik}...");
        $hasilFinal = $this->jalankanKMeansN_Dimensi($dataset, (int) $kTerbaik);

        // 4. SIMPAN HASIL KE DATABASE
        foreach ($hasilFinal['klaster'] as $idToko => $indexKlaster) {
            $namaKlaster = "Klaster " . ($indexKlaster + 1);
            DB::table('toko')->where('id', $idToko)->update([
                'cluster' => $namaKlaster,
                'updated_at' => now()
            ]);
        }

        $this->info("Luar Biasa! Toko berhasil dikelompokkan menjadi {$kTerbaik} klaster berdasarkan selera produk.");
    }

    // FUNGSI INTI: K-MEANS N-DIMENSI
    private function jalankanKMeansN_Dimensi($dataset, $k)
    {
        $maxIterasi = 100;
        $keysToko = array_keys($dataset);
        $keysProduk = array_keys(current($dataset));
        
        // Inisialisasi Centroid Acak
        $centroids = [];
        $acakKeys = $keysToko;
        shuffle($acakKeys);
        for ($i = 0; $i < $k; $i++) {
            $centroids[$i] = $dataset[$acakKeys[$i]];
        }

        $hasilKlaster = [];
        $iterasi = 0;

        while ($iterasi < $maxIterasi) {
            $klasterBaru = [];

            // A. Hitung Jarak Euclidean N-Dimensi
            foreach ($dataset as $idToko => $dataProduk) {
                $jarakTerdekat = PHP_INT_MAX;
                $indeksKlaster = 0;

                foreach ($centroids as $cIndex => $centroid) {
                    $totalKuadrat = 0;
                    // Rumus Jarak N-Dimensi
                    foreach ($keysProduk as $idProduk) {
                        $totalKuadrat += pow($dataProduk[$idProduk] - $centroid[$idProduk], 2);
                    }
                    $jarak = sqrt($totalKuadrat);

                    if ($jarak < $jarakTerdekat) {
                        $jarakTerdekat = $jarak;
                        $indeksKlaster = $cIndex;
                    }
                }
                $klasterBaru[$idToko] = $indeksKlaster;
            }

            if ($klasterBaru === $hasilKlaster) {
                break; // Konvergen (Titik Selesai)
            }
            $hasilKlaster = $klasterBaru;

            // B. Update Posisi Centroid Baru
            $jumlahPerKlaster = array_fill(0, $k, 0);
            $sumProdukPerKlaster = [];
            for ($i = 0; $i < $k; $i++) {
                $sumProdukPerKlaster[$i] = array_fill_keys($keysProduk, 0);
            }

            foreach ($hasilKlaster as $idToko => $cIndex) {
                $jumlahPerKlaster[$cIndex]++;
                foreach ($keysProduk as $idProduk) {
                    $sumProdukPerKlaster[$cIndex][$idProduk] += $dataset[$idToko][$idProduk];
                }
            }

            for ($i = 0; $i < $k; $i++) {
                if ($jumlahPerKlaster[$i] > 0) {
                    foreach ($keysProduk as $idProduk) {
                        $centroids[$i][$idProduk] = $sumProdukPerKlaster[$i][$idProduk] / $jumlahPerKlaster[$i];
                    }
                }
            }
            $iterasi++;
        }

        // C. Hitung WCSS (Within-Cluster Sum of Squares) untuk Elbow Method
        $wcss = 0;
        foreach ($hasilKlaster as $idToko => $cIndex) {
            $centroid = $centroids[$cIndex];
            $dataProduk = $dataset[$idToko];
            $jarakKuadrat = 0;
            
            foreach ($keysProduk as $idProduk) {
                $jarakKuadrat += pow($dataProduk[$idProduk] - $centroid[$idProduk], 2);
            }
            // Tambahkan langsung kuadrat jaraknya ke total WCSS
            $wcss += $jarakKuadrat;
        }

        return [
            'klaster' => $hasilKlaster,
            'wcss' => $wcss
        ];
    }
}