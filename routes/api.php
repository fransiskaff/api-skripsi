<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\SalesmanController;

// ==========================================
// 1. ZONA PUBLIK
// ==========================================
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// 2. ZONA RAHASIA
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- Dashboard ---
    Route::get('/dashboard-summary', [DashboardController::class, 'index']);
    Route::get('/dashboard/penjualan-area', [DashboardController::class, 'penjualanPerArea']);

    // --- Master Produk ---
    Route::get('/produk', [ProdukController::class, 'index']);
    Route::post('/produk', [ProdukController::class, 'store']);
    Route::get('/produk/{id}', [ProdukController::class, 'show']);
    Route::post('/produk/import', [ProdukController::class, 'importExcel']);
    Route::post('/produk/mutasi', [ProdukController::class, 'catatMutasi']);
    Route::post('/produk/tambah-stok', [ProdukController::class, 'tambahStok']);
    Route::get('/mutasi', [ProdukController::class, 'riwayatMutasi']);
    Route::delete('/produk/{id}', [ProdukController::class, 'destroy']);
    Route::put('/produk/{id}', [ProdukController::class, 'update']);

    // --- Master Toko & AI K-Means ---
    Route::get('/toko', [TokoController::class, 'index']);
    Route::post('/toko', [TokoController::class, 'store']);
    Route::post('/toko/clustering', [TokoController::class, 'runClustering']); 
    Route::delete('/toko/{id}', [TokoController::class, 'destroy']);

    // --- Master Salesman ---
    Route::get('/salesman', [SalesmanController::class, 'index']);
    Route::post('/salesman', [SalesmanController::class, 'store']);
    Route::put('/salesman/{id}/target', [SalesmanController::class, 'updateTarget']);
    Route::put('/salesman/{id}', [SalesmanController::class, 'update']); 
    Route::delete('/salesman/{id}', [SalesmanController::class, 'destroy']);

    // --- Transaksi & Penjualan ---
    Route::post('/transaksi', [TransaksiController::class, 'store']); 
    Route::get('/transaksi', [TransaksiController::class, 'index']);
    Route::delete('/transaksi/{id}', [TransaksiController::class, 'destroy']);
    Route::get('/transaksi/riwayat', [TransaksiController::class, 'riwayatPenjualan']);
    
    Route::get('/penjualan', [PenjualanController::class, 'index']);
    Route::post('/penjualan', [PenjualanController::class, 'store']);
    Route::get('/penjualan/{id}', [PenjualanController::class, 'show']);

    // --- AI Rekomendasi (K-Means) ---

    // 1. Rute Global untuk Dashboard
    Route::get('/rekomendasi', function () {
        $rekomendasi = DB::table('detail_penjualan')
            ->join('produk', 'detail_penjualan.id_produk', '=', 'produk.id')
            ->select('produk.nama_produk', DB::raw('SUM(detail_penjualan.qty) as total_terjual'))
            ->groupBy('produk.id', 'produk.nama_produk')
            ->orderBy('total_terjual', 'desc')
            ->limit(3)
            ->get();
            
        return response()->json($rekomendasi);
    });

    // 2. Rute Spesifik per Toko
    Route::get('/rekomendasi/{id_toko}', function ($id_toko) {
        $toko = DB::table('toko')->where('id', $id_toko)->first();
        if (!$toko) { return response()->json(['message' => 'Toko tidak ditemukan'], 404); }

        $klasterToko = $toko->cluster;
        $produkPernahDibeli = DB::table('penjualan')
            ->join('detail_penjualan', 'penjualan.id', '=', 'detail_penjualan.id_penjualan')
            ->where('penjualan.id_toko', $id_toko)
            ->pluck('detail_penjualan.id_produk'); 

        $rekomendasi = DB::table('penjualan')
            ->join('toko', 'penjualan.id_toko', '=', 'toko.id')
            ->join('detail_penjualan', 'penjualan.id', '=', 'detail_penjualan.id_penjualan')
            ->join('produk', 'detail_penjualan.id_produk', '=', 'produk.id')
            ->where('toko.cluster', $klasterToko)
            ->whereNotIn('produk.id', $produkPernahDibeli)
            ->select('produk.nama_produk', DB::raw('SUM(detail_penjualan.qty) as total_terjual')) 
            ->groupBy('produk.id', 'produk.nama_produk')
            ->orderBy('total_terjual', 'desc')
            ->limit(3)
            ->get();

        $source = 'K-Means (Cluster Best Seller)';
        if ($rekomendasi->isEmpty()) {
            $rekomendasi = DB::table('detail_penjualan')
                ->join('produk', 'detail_penjualan.id_produk', '=', 'produk.id')
                ->select('produk.nama_produk', DB::raw('SUM(detail_penjualan.qty) as total_terjual'))
                ->groupBy('produk.id', 'produk.nama_produk')
                ->orderBy('total_terjual', 'desc')
                ->limit(3)
                ->get();
            $source = 'Top Global Best Seller (Fallback)';
        }

        return response()->json([
            'id_toko'     => $toko->id,
            'nama_toko'   => $toko->nama_toko,
            'cluster'     => $toko->cluster,
            'rekomendasi' => $rekomendasi->pluck('nama_produk'),
            'source'      => $source
        ]);
    });
});