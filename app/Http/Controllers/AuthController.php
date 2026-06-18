<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. TARUH LOG DI SINI (Baris paling pertama)
        \Illuminate\Support\Facades\Log::info('Payload dari React:', $request->all());

        // 2. Validasi inputan dari React
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 3. Cek kecocokan di database
        if (Auth::attempt($request->only('email', 'password'))) {
            /** @var User $user */
            $user = Auth::user();
            
            // Cetak Token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // Kirim token ke React
            return response()->json([
                'message' => 'Login Berhasil',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        }

        // 4. Tambahkan log ini untuk melihat email apa yang sebenarnya ditolak
        \Illuminate\Support\Facades\Log::warning('Login ditolak untuk email: ' . $request->email);

        // Jika salah, tolak!
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}