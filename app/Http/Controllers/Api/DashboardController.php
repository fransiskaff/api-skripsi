<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
{
    try {
        // 1. Statistik Dasar
        $totalTransaksi = DB::table('penjualan')->count(); 
        $totalStok = DB::table('produk')->sum('stok_fisik');
        $stokMenipis = DB::table('produk')->where('stok_fisik', '<=', 10)->count();

        // 2. Transaksi Terbaru (Disesuaikan dengan tabel tanpa id_salesman)
        $transaksiTerbaru = DB::table('penjualan')
            ->join('toko', 'penjualan.id_toko', '=', 'toko.id')
            ->leftJoin('detail_penjualan', 'penjualan.id', '=', 'detail_penjualan.id_penjualan')
            ->select(
                'penjualan.id as id_nota', 
                'toko.nama_toko', 
                'toko.area',
                DB::raw('COALESCE(SUM(detail_penjualan.qty), 0) as total_item'), 
                DB::raw("'Selesai' as status") 
            )
            ->groupBy('penjualan.id', 'toko.nama_toko', 'toko.area', 'penjualan.tanggal_transaksi')
            ->orderBy('penjualan.tanggal_transaksi', 'desc')
            ->limit(5) 
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'statistik' => [
                    'total_transaksi' => $totalTransaksi, 
                    'total_stok'      => (int) $totalStok,
                    'stok_menipis'    => $stokMenipis,
                    'total_toko'      => DB::table('toko')->count(), // Ganti salesman_aktif dengan total_toko
                ],
                'transaksi_terbaru' => $transaksiTerbaru
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal memuat data dashboard: ' . $e->getMessage()
        ], 500);
    }
}

public function penjualanPerArea()
    {
        try {
            // Melakukan Join antara tabel penjualan dan toko, lalu dikelompokkan per area
            $data = DB::table('penjualan')
                ->join('toko', 'penjualan.id_toko', '=', 'toko.id')
                ->select(
                    'toko.area', 
                    DB::raw('SUM(penjualan.total_harga) as total_penjualan')
                )
                ->groupBy('toko.area')
                ->orderBy('total_penjualan', 'desc') // Urutkan dari penjualan tertinggi
                ->get();

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menarik data analitik: ' . $e->getMessage()], 500);
        }
    }
}