<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RunKMeansClustering extends Command
{
    protected $signature = 'ai:run-kmeans';
    protected $description = 'Menjalankan algoritma K-Means + RFM untuk klasterisasi toko';

    public function handle()
    {
        Log::info('K-Means AI: Memulai ekstraksi data RFM...');

        try {
            $tokoList = DB::table('toko')->get();
            if ($tokoList->isEmpty()) {
                $this->info('Tidak ada data toko.');
                return Command::SUCCESS;
            }

            // --- FASE 1: EKSTRAKSI & TRANSFORMASI DATA (Hitung RFM) ---
            $rfmData = [];
            $hariIni = Carbon::now();

            foreach ($tokoList as $toko) {
                // Tarik data transaksi per toko
                $transaksi = DB::table('penjualan')
                    ->where('id_toko', $toko->id)
                    ->orderBy('tanggal_transaksi', 'desc')
                    ->get();

                if ($transaksi->isEmpty()) {
                    continue; // Lewati toko yang belum punya transaksi sama sekali
                }

                // Recency: Selisih hari transaksi terakhir dengan hari ini
                $tanggalTerakhir = Carbon::parse($transaksi->first()->tanggal_transaksi);
                $recency = $hariIni->diffInDays($tanggalTerakhir);

                // Frequency: Total transaksi
                $frequency = $transaksi->count();

                // Monetary (Magnitude): Total QTY barang yang diambil
                $monetary = DB::table('penjualan')
                    ->join('detail_penjualan', 'penjualan.id', '=', 'detail_penjualan.id_penjualan')
                    ->where('penjualan.id_toko', $toko->id)
                    ->sum('detail_penjualan.qty');

                $rfmData[] = [
                    'id_toko' => $toko->id,
                    'R' => $recency,
                    'F' => $frequency,
                    'M' => (int) $monetary,
                ];
            }

            if (count($rfmData) < 3) {
                $this->info('Data toko yang bertransaksi kurang dari 3. K-Means butuh lebih banyak data.');
                return Command::SUCCESS;
            }

            // --- FASE 2: PROSES K-MEANS CLUSTERING ---
            // 1. Inisialisasi Centroid Awal (Pilih 3 titik acak sebagai pusat klaster)
            $k = 3;
            $centroids = [];
            $acakIndex = array_rand($rfmData, $k);
            foreach ($acakIndex as $index) {
                $centroids[] = [
                    'R' => $rfmData[$index]['R'],
                    'F' => $rfmData[$index]['F'],
                    'M' => $rfmData[$index]['M'],
                ];
            }

            $maxIterasi = 10; // Cukup 10 iterasi untuk data skala kecil-menengah
            $hasilKlaster = [];

            for ($iterasi = 0; $iterasi < $maxIterasi; $iterasi++) {
                $klasterBaru = [];
                
                // 2. Hitung Jarak Euclidean & Kelompokkan
                foreach ($rfmData as $data) {
                    $jarakTerdekat = INF;
                    $indexKlaster = 0;

                    foreach ($centroids as $cIndex => $centroid) {
                        // Rumus Euclidean Distance
                        $jarak = sqrt(
                            pow($data['R'] - $centroid['R'], 2) +
                            pow($data['F'] - $centroid['F'], 2) +
                            pow($data['M'] - $centroid['M'], 2)
                        );

                        if ($jarak < $jarakTerdekat) {
                            $jarakTerdekat = $jarak;
                            $indexKlaster = $cIndex + 1; // Klaster 1, 2, atau 3
                        }
                    }
                    $klasterBaru[$data['id_toko']] = $indexKlaster;
                }

                $hasilKlaster = $klasterBaru;

                // 3. Perbarui Nilai Centroid (Hitung rata-rata titik di tiap klaster)
                $sum = [1 => ['R'=>0,'F'=>0,'M'=>0,'count'=>0], 2 => ['R'=>0,'F'=>0,'M'=>0,'count'=>0], 3 => ['R'=>0,'F'=>0,'M'=>0,'count'=>0]];
                
                foreach ($rfmData as $data) {
                    $c = $hasilKlaster[$data['id_toko']];
                    $sum[$c]['R'] += $data['R'];
                    $sum[$c]['F'] += $data['F'];
                    $sum[$c]['M'] += $data['M'];
                    $sum[$c]['count']++;
                }

                foreach ($centroids as $cIndex => $centroid) {
                    $c = $cIndex + 1;
                    if ($sum[$c]['count'] > 0) {
                        $centroids[$cIndex]['R'] = $sum[$c]['R'] / $sum[$c]['count'];
                        $centroids[$cIndex]['F'] = $sum[$c]['F'] / $sum[$c]['count'];
                        $centroids[$cIndex]['M'] = $sum[$c]['M'] / $sum[$c]['count'];
                    }
                }
            }

            // --- FASE 3: UPDATE DATABASE MASSAL (Load) ---
            DB::beginTransaction();
            try {
                foreach ($hasilKlaster as $idToko => $klaster) {
                    DB::table('toko')
                        ->where('id', $idToko)
                        ->update(['cluster' => 'Cluster ' . $klaster]);
                }
                DB::commit();
                Log::info('K-Means AI: Klasterisasi selesai dan disimpan.');
                $this->info('K-Means AI berjalan sukses!');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('K-Means Gagal: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}