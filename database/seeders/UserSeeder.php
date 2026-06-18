<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Membuat Akun Admin
        User::create([
            'name' => 'Admin Pusat',
            'email' => 'admin@xyz.com',
            'password' => Hash::make('rahasia123'), 
            'role' => 'admin',
        ]);

        // Membuat Akun Salesman
        User::create([
            'name' => 'Budi Salesman',
            'email' => 'salesman@xyz.com',
            'password' => Hash::make('rahasia123'),
            'role' => 'salesman',
            'target_bulanan' => 50000000
        ]);
    }
}