<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Toko;

class TokoController extends Controller
{
public function index() {
    $toko = DB::table('toko')
              ->select('id', 'nama_toko', 'alamat', 'area', 'cluster') 
              ->get();
    return response()->json($toko, 200);
}

    public function store(Request $request)
    {
        // 1. Validasi disesuaikan dengan struktur database asli
        $request->validate([
            'nama_toko'   => 'required',
            'alamat'      => 'required', // Kolom asli: alamat
            'area'        => 'required'
        ]);

        // 2. Simpan data sesuai kolom baru
        Toko::create([
            'nama_toko'        => $request->nama_toko,
            'alamat'           => $request->alamat,
            'area'             => $request->area,
            'koordinat_lokasi' => '0,0', // Kolom asli: koordinat_lokasi
            'id_salesman'      => null,
            'cluster'          => null 
        ]);

        return response()->json(['message' => 'Toko berhasil ditambahkan!'], 201);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $penjualan = \App\Models\Penjualan::where('id_toko', $id)->get();
            foreach ($penjualan as $p) {
                DB::table('detail_penjualan')->where('id_penjualan', $p->id)->delete();
                $p->delete();
            }

            Toko::findOrFail($id)->delete();

            DB::commit();
            return response()->json(['message' => 'Toko berhasil dihapus permanen.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus toko: ' . $e->getMessage()], 500);
        }
    }

    // --- FUNGSI AI K-MEANS BERBASIS RFM ---
    public function runClustering()
    {
        try {
            // 1. Ekstraksi Data RFM (Recency, Frequency, Monetary)
            $hariIni = now();
            $tokoData = DB::table('penjualan')
                ->select('id_toko', 
                    DB::raw('MAX(tanggal_transaksi) as last_trx'),
                    DB::raw('COUNT(id) as frequency'),
                    DB::raw('SUM(total_harga) as monetary')
                )
                ->groupBy('id_toko')
                ->get();

            if ($tokoData->count() < 3) {
                return response()->json(['message' => 'Gagal: Minimal harus ada 3 toko yang memiliki transaksi.'], 400);
            }

            $dataset = [];
            $minR = 999999; $maxR = 0;
            $minF = 999999; $maxF = 0;
            $minM = 999999999; $maxM = 0;

            // Kumpulkan data dan cari nilai Min-Max untuk normalisasi
            foreach ($tokoData as $row) {
                $recency = \Carbon\Carbon::parse($row->last_trx)->diffInDays($hariIni);
                $frequency = $row->frequency;
                $monetary = $row->monetary;

                if($recency < $minR) $minR = $recency;
                if($recency > $maxR) $maxR = $recency;
                if($frequency < $minF) $minF = $frequency;
                if($frequency > $maxF) $maxF = $frequency;
                if($monetary < $minM) $minM = $monetary;
                if($monetary > $maxM) $maxM = $monetary;

                $dataset[$row->id_toko] = ['R' => $recency, 'F' => $frequency, 'M' => $monetary];
            }

            // 2. Normalisasi Data (Skala 0 - 1) agar nominal uang tidak merusak perhitungan jarak Euclidean
            $normalizedData = [];
            foreach ($dataset as $idToko => $data) {
                $normR = ($maxR - $minR == 0) ? 0 : ($data['R'] - $minR) / ($maxR - $minR);
                $normF = ($maxF - $minF == 0) ? 0 : ($data['F'] - $minF) / ($maxF - $minF);
                $normM = ($maxM - $minM == 0) ? 0 : ($data['M'] - $minM) / ($maxM - $minM);
                
                $normalizedData[$idToko] = ['R' => $normR, 'F' => $normF, 'M' => $normM];
            }

            // 3. Inisialisasi K-Means (K=3)
            $k = 3;
            $centroidKeys = array_rand($normalizedData, $k);
            $centroids = [];
            foreach ($centroidKeys as $idx => $key) {
                $centroids[$idx] = $normalizedData[$key];
            }

            // 4. Perulangan K-Means (Maks 10 iterasi)
            $clusters = [];
            for ($iter = 0; $iter < 10; $iter++) {
                $clusters = [0 => [], 1 => [], 2 => []];
                
                // Assign data ke centroid terdekat (Jarak Euclidean)
                foreach ($normalizedData as $idToko => $data) {
                    $minDist = 999999;
                    $clusterTerpilih = 0;

                    foreach ($centroids as $idx => $c) {
                        $distance = sqrt(
                            pow($data['R'] - $c['R'], 2) + 
                            pow($data['F'] - $c['F'], 2) + 
                            pow($data['M'] - $c['M'], 2)
                        );
                        if ($distance < $minDist) {
                            $minDist = $distance;
                            $clusterTerpilih = $idx;
                        }
                    }
                    $clusters[$clusterTerpilih][] = $idToko;
                }

                // Perbarui posisi Centroid (Rata-rata titik anggota)
                $newCentroids = [];
                foreach ($clusters as $idx => $anggotaToko) {
                    if (count($anggotaToko) > 0) {
                        $sumR = 0; $sumF = 0; $sumM = 0;
                        foreach ($anggotaToko as $idToko) {
                            $sumR += $normalizedData[$idToko]['R'];
                            $sumF += $normalizedData[$idToko]['F'];
                            $sumM += $normalizedData[$idToko]['M'];
                        }
                        $newCentroids[$idx] = [
                            'R' => $sumR / count($anggotaToko),
                            'F' => $sumF / count($anggotaToko),
                            'M' => $sumM / count($anggotaToko)
                        ];
                    } else {
                        $newCentroids[$idx] = $centroids[$idx]; 
                    }
                }
                
                if ($centroids == $newCentroids) break; // Berhenti jika konvergen
                $centroids = $newCentroids;
            }

            // 5. Simpan Hasil Klaster ke Master Toko
            DB::beginTransaction();
            DB::table('toko')->update(['cluster' => null]); // Bersihkan label lama
            
            foreach ($clusters as $idx => $anggotaToko) {
                $namaCluster = "Cluster " . ($idx + 1); 
                if (count($anggotaToko) > 0) {
                    DB::table('toko')->whereIn('id', $anggotaToko)->update(['cluster' => $namaCluster]);
                }
            }
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Analisis AI K-Means selesai. Semua toko telah dikelompokkan!'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghitung AI: ' . $e->getMessage()], 500);
        }
    }
}