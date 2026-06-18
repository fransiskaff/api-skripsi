<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware; // Import fitur bypass

class TokoApiTest extends TestCase
{
    // Gunakan fitur ini agar Laravel mematikan gembok Sanctum khusus saat test berjalan
    use WithoutMiddleware; 

    public function test_api_berhasil_mengambil_data_master_toko()
    {
        // Eksekusi: Tembak endpoint API
        $response = $this->getJson('/api/toko');

        // Validasi: Pastikan sistem merespons dengan status 200 (OK)
        $response->assertStatus(200);
    }
}