<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index()
    {
        $riwayat = DB::table('penjualan')
            ->join('toko', 'penjualan.id_toko', '=', 'toko.id')
            ->join('detail_penjualan', 'penjualan.id', '=', 'detail_penjualan.id_penjualan')
            ->join('produk', 'detail_penjualan.id_produk', '=', 'produk.id')
            ->select(
                'penjualan.id as id_nota',
                'penjualan.tanggal_transaksi as waktu',
                'toko.nama_toko',
                'toko.area',
                'produk.nama_produk as nama_barang',
                'detail_penjualan.qty as qty_keluar'
            )
            ->orderBy('penjualan.tanggal_transaksi', 'desc')
            ->get();

        return response()->json($riwayat, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_toko'   => 'required',
            'keranjang' => 'required|array', 
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->keranjang as $item) {
                $produk = DB::table('produk')->where('id', $item['id_produk'])->first();
                if (!$produk) {
                    throw new \Exception("Barang dengan ID {$item['id_produk']} tidak ditemukan.");
                }
                if ($produk->stok_fisik < $item['qty']) {
                    throw new \Exception("Stok tidak cukup untuk {$produk->nama_produk}.");
                }
            }

            $idUser = auth('sanctum')->check() ? auth('sanctum')->user()->id : 1; 

            // PERBAIKAN: Gunakan id_user sesuai database skripsi
            $idPenjualan = DB::table('penjualan')->insertGetId([
                'id_toko'           => $request->id_toko,
                'id_user'           => $idUser, 
                'tanggal_transaksi' => now(),
                'total_harga'       => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            foreach ($request->keranjang as $item) {
                DB::table('detail_penjualan')->insert([
                    'id_penjualan' => $idPenjualan,
                    'id_produk'    => $item['id_produk'],
                    'qty'          => $item['qty'],
                    'harga_satuan'   => 0,
                ]);

                DB::table('produk')
                    ->where('id', $item['id_produk'])
                    ->decrement('stok_fisik', $item['qty']);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Transaksi berhasil.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $detailPenjualan = DB::table('detail_penjualan')->where('id_penjualan', $id)->get();
            foreach ($detailPenjualan as $detail) {
                DB::table('produk')->where('id', $detail->id_produk)->increment('stok_fisik', $detail->qty);
            }
            DB::table('detail_penjualan')->where('id_penjualan', $id)->delete();
            DB::table('penjualan')->where('id', $id)->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Transaksi dihapus.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function riwayatPenjualan(Request $request)
    {
        try {
            $salesmanId = $request->user()->id;

          
            $data = DB::table('penjualan')
                ->join('toko', 'penjualan.id_toko', '=', 'toko.id')
                ->join('detail_penjualan', 'penjualan.id', '=', 'detail_penjualan.id_penjualan')
                ->join('produk', 'detail_penjualan.id_produk', '=', 'produk.id')
                //->whereNotNull('penjualan.id_user')
                ->select(
                    'penjualan.id',
                    'penjualan.created_at',
                    'toko.nama_toko',
                    'detail_penjualan.qty',
                    'produk.harga_jual', 
                    'produk.nama_produk'
                )
                ->orderBy('penjualan.created_at', 'desc')
                ->get();

            $formattedData = [];
            foreach ($data as $row) {
                $id = $row->id;
                if (!isset($formattedData[$id])) {
                    $formattedData[$id] = [
                        'id' => $id,
                        'kode_nota' => 'TRX-' . str_pad($id, 4, '0', STR_PAD_LEFT),
                        'nama_toko' => $row->nama_toko,
                        'tanggal' => date('d M Y H:i', strtotime($row->created_at)),
                        'total_item' => 0,
                        'total_harga' => 0,
                        'items' => []
                    ];
                }
                $harga = $row->harga_jual ?? 0;
                $subtotal = $row->qty * $harga;
                $formattedData[$id]['items'][] = [
                    'nama_produk' => $row->nama_produk,
                    'qty' => $row->qty,
                    'harga_satuan' => $harga,
                    'subtotal' => $subtotal
                ];
                $formattedData[$id]['total_item'] += $row->qty;
                $formattedData[$id]['total_harga'] += $subtotal;
            }

            return response()->json(['status' => 'success', 'data' => array_values($formattedData)], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}