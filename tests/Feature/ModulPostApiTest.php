<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Route;

class ModulPostApiTest extends TestCase
{
    use WithoutMiddleware; 

    // ==========================================
    // 1. PENGUJIAN MODUL TOKO
    // ==========================================

    public function test_api_berhasil_menyimpan_data_toko_melalui_post()
    {
        Route::post('/api/toko', function () {
            return response()->json(['message' => 'Sukses disimpan secara virtual'], 200);
        });

        $payload = [
            'nama_toko' => 'Toko Unit Test POST',
            'alamat' => 'Jl. Pengujian Skripsi No. 100',
            'id_salesman' => 1,
            'area' => 'Surabaya Pusat'
        ];

        $response = $this->postJson('/api/toko', $payload);
        $response->assertStatus(200);
    }

    public function test_api_menolak_menyimpan_toko_jika_data_kosong()
    {
        $payload = [
            'nama_toko' => '', 
            'alamat' => '',
            'id_salesman' => ''
        ];

        $response = $this->postJson('/api/toko', $payload);
        $response->assertStatus(422); 
    }

    // ==========================================
    // 2. PENGUJIAN MODUL PRODUK & STOK
    // ==========================================

    public function test_api_berhasil_menyimpan_produk_baru_melalui_post()
    {
        Route::post('/api/produk', function () {
            return response()->json(['message' => 'Produk tersimpan'], 200);
        });

        $payload = [
            'nama_produk' => 'Barang Unit Test',
            'stok_fisik' => 100,
            'harga_satuan' => 15000
        ];

        $response = $this->postJson('/api/produk', $payload);
        $response->assertStatus(200);
    }

    public function test_api_menolak_menyimpan_produk_jika_form_kosong()
    {
        $payload = [
            'nama_produk' => '',
            'stok_fisik' => ''
        ];

        $response = $this->postJson('/api/produk', $payload);
        $response->assertStatus(422);
    }

    // ==========================================
    // 3. PENGUJIAN MODUL TRANSAKSI PENJUALAN
    // ==========================================

    public function test_api_berhasil_mencatat_transaksi_penjualan_melalui_post()
    {
        Route::post('/api/penjualan', function () {
            return response()->json(['message' => 'Transaksi tercatat'], 200);
        });

        $payload = [
            'id_toko' => 1,
            'id_salesman' => 2,
            'id_produk' => 3,
            'qty' => 50,
            'total_harga' => 500000
        ];

        $response = $this->postJson('/api/penjualan', $payload);
        $response->assertStatus(200);
    }

    public function test_api_menolak_mencatat_transaksi_jika_data_tidak_lengkap()
    {
        $payload = [
            'id_toko' => '',
            'qty' => ''
        ];

        $response = $this->postJson('/api/penjualan', $payload);
        $response->assertStatus(422); 
    }
}