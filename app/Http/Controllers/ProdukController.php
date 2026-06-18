<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\MutasiBarang;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    public function index()
    {
        // PERBAIKAN: Tarik semua data (karena tidak ada kolom is_active di DB)
        $produk = Produk::all();
        return response()->json($produk);
    }

    public function update(Request $request, $id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['error' => 'Produk tidak ditemukan'], 404);

        // PERBAIKAN: Tambahkan validasi agar SKU tidak tabrakan dengan produk lain
        $request->validate([
            'sku' => 'required|unique:produk,sku,' . $id, // Abaikan SKU miliknya sendiri saat dicek
            'nama_produk' => 'required|string',
            'harga_jual' => 'required|numeric',
            'isi_per_kardus' => 'required|integer|min:1'
        ]);

        $produk->update($request->all());
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Data produk berhasil diubah', 
            'data' => $produk
        ]);
    }

public function store(Request $request)
    {
        $request->validate([
            'sku' => 'required|unique:produk',
            'nama_produk' => 'required|string',
            'harga_jual' => 'required|integer',
            'isi_per_kardus' => 'required|integer|min:1', // <-- TAMBAHKAN INI
            'stok_fisik' => 'integer',
            'safety_stock' => 'integer',
            'expired_date' => 'nullable|date'
        ]);

        $produk = Produk::create($request->all());
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Produk berhasil ditambahkan', 
            'data' => $produk
        ], 201);
    }

    public function show(int $id)
    {
        $produk = Produk::find($id);
        if (!$produk) return response()->json(['error' => 'Produk tidak ditemukan'], 404);
        
        return response()->json(['status' => 'success', 'data' => $produk]);
    }

public function destroy($id)
{
    try {
        DB::beginTransaction();

        // 1. Hapus semua riwayat mutasi yang terkait dengan produk ini
        DB::table('mutasi_barang')->where('id_produk', $id)->delete();

        // 2. Hapus produk itu sendiri secara permanen
        $produk = \App\Models\Produk::findOrFail($id);
        $produk->delete(); 

        DB::commit();

        return response()->json(['message' => 'Produk dan seluruh riwayatnya berhasil dihapus permanen.'], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
    }
}
    public function catatMutasi(Request $request)
    {
        $request->validate([
            'id_produk' => 'required|exists:produk,id',
            'jenis_mutasi' => 'required|in:masuk,keluar,retur',
            'jumlah' => 'required|integer|min:1',
            'keterangan' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $produk = Produk::find($request->id_produk);

            MutasiBarang::create([
                'id_produk' => $request->id_produk,
                'jenis_mutasi' => $request->jenis_mutasi,
                'jumlah' => $request->jumlah,
                'tanggal_mutasi' => now(),
                'keterangan' => $request->keterangan
            ]);

            if ($request->jenis_mutasi == 'masuk' || $request->jenis_mutasi == 'retur') {
                $produk->stok_fisik += $request->jumlah;
            } else if ($request->jenis_mutasi == 'keluar') {
                if ($produk->stok_fisik < $request->jumlah) {
                    return response()->json(['error' => 'Stok tidak mencukupi'], 400);
                }
                $produk->stok_fisik -= $request->jumlah;
            }
            
            $produk->save();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Mutasi berhasil dicatat, stok diperbarui',
                'sisa_stok' => $produk->stok_fisik
            ]);

        }  catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'error' => 'Terjadi kesalahan sistem',
                'detail' => $e->getMessage() 
            ], 500);
        }
    }

    public function riwayatMutasi()
    {
        // Join tabel mutasi dengan produk agar nama produk & SKU terbaca
        $mutasi = MutasiBarang::join('produk', 'mutasi_barang.id_produk', '=', 'produk.id')
            ->select('mutasi_barang.*', 'produk.nama_produk', 'produk.sku')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $mutasi]);
    }

    // Fungsi menambah kuantitas stok fisik dari form InputStok.jsx (Web Admin)
    public function tambahStok(Request $request)
    {
        $request->validate([
            'id_produk' => 'required',
            'qty' => 'required|integer|min:1',
        ]);

        try {
            DB::table('produk')
                ->where('id', $request->id_produk)
                ->increment('stok_fisik', $request->qty);

            return response()->json([
                'status' => 'success',
                'message' => 'Stok fisik produk berhasil diperbarui di gudang.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui stok: ' . $e->getMessage()
            ], 500);
        }
    }
}