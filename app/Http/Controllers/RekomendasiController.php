<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class RekomendasiController extends Controller
{
    public function show($idToko)
    {
        $toko = DB::table('toko')->where('id', $idToko)->first();

        if (!$toko) {
            return response()->json(['message' => 'Toko tidak ditemukan'], 404);
        }

        $namaKlaster = $toko->cluster ?? 'Belum Terklasifikasi';

        return response()->json([
            'cluster' => $namaKlaster,
            'rekomendasi' => [
                'Oli Mesin Matic 800ml',
                'Kampas Rem Depan',
                'Busi Standar'
            ]
        ]);
    }
}