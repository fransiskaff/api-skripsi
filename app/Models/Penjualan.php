<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $table = 'penjualan';
    
    protected $fillable = [
        'id_toko', 
        'id_salesman', 
        'tanggal_transaksi', 
        'total_harga'
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'id_toko', 'id');
    }
}