<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class PenjualanApiTest extends TestCase
{
    use WithoutMiddleware; 

    public function test_api_berhasil_memuat_riwayat_transaksi_penjualan()
    {
        // Menguji fitur utama riwayat transaksi yang menjadi bahan baku K-Means
        $response = $this->getJson('/api/penjualan');
        $response->assertStatus(200);
    }
}