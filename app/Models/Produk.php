<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';
    
    protected $fillable = [
        'sku', 
        'nama_produk', 
        'harga_jual', 
        'stok_fisik', 
        'safety_stock', 
        'isi_per_kardus', 
        'expired_date', 
        'is_active'
    ];

  
    public function mutasi()
    {
        return $this->hasMany(MutasiBarang::class, 'id_produk', 'id');
    }
}