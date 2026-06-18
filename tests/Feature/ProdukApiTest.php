<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ProdukApiTest extends TestCase
{
    use WithoutMiddleware; 

    public function test_api_berhasil_mengambil_master_data_produk_dan_stok()
    {
        // Menguji fitur utama pemantauan inventaris/stok barang
        $response = $this->getJson('/api/produk');
        $response->assertStatus(200);
    }
}