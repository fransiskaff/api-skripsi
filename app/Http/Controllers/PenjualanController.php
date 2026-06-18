<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Produk;
use App\Models\MutasiBarang;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PenjualanController extends Controller
{
    // Tampilkan Semua Riwayat Transaksi (Untuk Tabel Dasbor)
    public function index()
    {
        try {
            // Mengambil data penjualan beserta relasi toko untuk melihat area
            $penjualan = Penjualan::with(['toko'])->orderBy('tanggal_transaksi', 'desc')->get();
            
            return response()->json($penjualan, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan di server: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function show(int $id)
    {
        $penjualan = Penjualan::join('toko', 'penjualan.id_toko', '=', 'toko.id')
            ->select('penjualan.*', 'toko.nama_toko', 'toko.area')
            ->where('penjualan.id', $id)
            ->first();

        if (!$penjualan) return response()->json(['error' => 'Data tidak ditemukan'], 404);

        $detail = DetailPenjualan::join('produk', 'detail_penjualan.id_produk', '=', 'produk.id')
            ->select('detail_penjualan.*', 'produk.nama_produk', 'produk.sku')
            ->where('id_penjualan', $id)
            ->get();

        return response()->json([
            'status' => 'success',
            'data_nota' => $penjualan,
            'detail_barang' => $detail
        ]);
    }

    // Catat Transaksi Baru
    public function store(Request $request)
    {
        // 1. Validasi Input dari Aplikasi Mobile/Web (Keamanan: total_harga dihitung di backend)
        $request->validate([
            'id_toko' => 'required|exists:toko,id',
            'items' => 'required|array',
            'items.*.id_produk' => 'required|exists:produk,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $totalHarga = 0;
            $detailPenjualan = [];

            // 2. Buat Header Nota (Tanpa id_salesman)
            $penjualan = Penjualan::create([
                'id_toko' => $request->id_toko,
                'tanggal_transaksi' => now()->format('Y-m-d'),
                'total_harga' => 0, // Akan diupdate setelah perhitungan detail
            ]);

            // 3. Proses setiap barang yang dibeli & Manajemen Stok
            foreach ($request->items as $item) {
                // Ambil data produk langsung dari database untuk mendapatkan harga jual asli
                $produk = Produk::find($item['id_produk']);
                
                // Pengecekan Ketersediaan Stok Gudang
                if ($produk->stok_fisik < $item['qty']) {
                    throw new \Exception("Gagal: Stok {$produk->nama_produk} sisa {$produk->stok_fisik}, tidak cukup untuk pesanan sejumlah {$item['qty']}.");
                }

                $subTotal = $produk->harga_jual * $item['qty'];
                $totalHarga += $subTotal;

                // Potong Stok Master
                $produk->stok_fisik -= $item['qty'];
                $produk->save();

                // Simpan ke array detail untuk di-insert
                DetailPenjualan::create([
                    'id_penjualan' => $penjualan->id,
                    'id_produk' => $item['id_produk'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $produk->harga_jual,
                ]);

                // Catat ke Buku Riwayat Mutasi Gudang
                MutasiBarang::create([
                    'id_produk' => $item['id_produk'],
                    'jenis_mutasi' => 'keluar',
                    'jumlah' => $item['qty'],
                    'tanggal_mutasi' => now(),
                    'keterangan' => "Penjualan Otomatis ke Toko ID: {$request->id_toko} via Nota #{$penjualan->id}"
                ]);
            }

            // 4. Update Grand Total di tabel Penjualan
            $penjualan->update(['total_harga' => $totalHarga]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi sukses dicatat dan stok gudang berhasil diperbarui',
                'data_nota' => $penjualan
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Transaksi Dibatalkan',
                'detail' => $e->getMessage()
            ], 400); 
        }
    }

    // Menghapus data transaksi (Khusus Admin) beserta Pemulihan Stok
    public function destroy(int $id)
    {
        DB::beginTransaction();
        try {
            $penjualan = Penjualan::find($id);
            if (!$penjualan) {
                throw new \Exception("Nota penjualan tidak ditemukan.");
            }

            // 1. Tarik semua detail barang dari nota yang akan dihapus
            $details = DetailPenjualan::where('id_penjualan', $id)->get();

            // 2. Kembalikan stok ke master produk dan catat mutasi masuk
            foreach ($details as $detail) {
                $produk = Produk::find($detail->id_produk);
                if ($produk) {
                    $produk->stok_fisik += $detail->qty;
                    $produk->save();

                    MutasiBarang::create([
                        'id_produk' => $detail->id_produk,
                        'jenis_mutasi' => 'masuk',
                        'jumlah' => $detail->qty,
                        'tanggal_mutasi' => now(),
                        'keterangan' => "Pembatalan/Penghapusan Nota #{$id}"
                    ]);
                }
            }

            // 3. Hapus detail barang (otomatis terhapus jika di migration pakai cascade, tapi manual lebih aman)
            DetailPenjualan::where('id_penjualan', $id)->delete();
            
            // 4. Hapus induk nota
            $penjualan->delete();

            DB::commit();
            return response()->json([
                'status' => 'success', 
                'message' => 'Transaksi berhasil dihapus dan stok barang telah dikembalikan ke gudang.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}