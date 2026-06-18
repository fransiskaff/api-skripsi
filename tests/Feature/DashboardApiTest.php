<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class DashboardApiTest extends TestCase
{
    use WithoutMiddleware; 

    public function test_api_berhasil_memuat_ringkasan_dashboard()
    {
        // Menguji apakah API dashboard berjalan normal untuk merender halaman utama
        $response = $this->getJson('/api/dashboard-summary');
        $response->assertStatus(200);
    }
}