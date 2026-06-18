<?php

namespace App\Imports;

use App\Models\Produk;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProdukImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Sesuaikan kata di dalam $row['...'] dengan judul kolom di Excel Anda nanti
        return new Produk([
            'sku'           => $row['sku'],
            'nama_produk'   => $row['nama_produk'],
            'kategori'      => $row['kategori'],
            'harga_jual'    => $row['harga'],
            'stok_fisik'    => $row['stok'],
        ]);
    }
}