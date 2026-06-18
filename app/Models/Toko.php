<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{
    protected $table = 'toko';

    protected $fillable = [
        'nama_toko', 
        'alamat', 
        'id_salesman',
        'area',      
        'cluster'    
    ];

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_toko', 'id');
    }
}