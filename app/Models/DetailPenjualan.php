<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';
    protected $fillable = ['id_penjualan', 'id_produk', 'qty', 'harga_satuan'];
}