<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiBarang extends Model
{
    use HasFactory;

    protected $table = 'mutasi_barang';

    protected $fillable = [
        'id_produk', 
        'jenis_mutasi', 
        'jumlah', 
        'tanggal_mutasi', 
        'keterangan'
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }
}