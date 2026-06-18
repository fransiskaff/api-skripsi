<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SalesmanController extends Controller
{
    public function index()
    {
        $salesmen = DB::table('users')
            ->where('role', 'salesman')
            // 1. Join ke Master Toko terlebih dahulu (sebagai jembatan)
            ->leftJoin('toko', 'users.id', '=', 'toko.id_salesman')
            
            // 2. Dari Toko baru kita Join ke tabel Penjualan
            ->leftJoin('penjualan', function($join) {
                $join->on('toko.id', '=', 'penjualan.id_toko')
                     ->whereMonth('penjualan.tanggal_transaksi', now()->month)
                     ->whereYear('penjualan.tanggal_transaksi', now()->year);
            })
            ->select(
                'users.id', 
                'users.name as nama_lengkap', 
                'users.email',
                'users.target_bulanan',
                DB::raw('COALESCE(SUM(penjualan.total_harga), 0) as total_pencapaian') 
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.target_bulanan')
            ->get();

        return response()->json($salesmen, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap'   => 'required|string',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'target_bulanan' => 'required|integer',
        ]);

        try {
            DB::table('users')->insert([
                'name'           => $request->nama_lengkap, 
                'email'          => $request->email,
                'password'       => Hash::make($request->password), 
                'role'           => 'salesman',
                'target_bulanan' => $request->target_bulanan,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return response()->json([
                'status' => 'success', 
                'message' => 'Akun Salesman berhasil dibuat dan siap digunakan.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal menambah salesman: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // 1. Validasi Input (Email boleh sama dengan miliknya sendiri)
        $request->validate([
            'nama_lengkap' => 'required|string',
            'email'        => 'required|email|unique:users,email,' . $id, 
            'password'     => 'nullable|string|min:6', // Password bersifat opsional
        ]);

        try {
            // 2. Siapkan data dasar yang diubah
            $updateData = [
                'name'       => $request->nama_lengkap,
                'email'      => $request->email,
                'updated_at' => now(),
            ];

            // 3. Jika admin mengisi password baru, maka ikut diubah
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            // 4. Eksekusi ke database
            DB::table('users')
                ->where('id', $id)
                ->where('role', 'salesman')
                ->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Data profil salesman berhasil diperbarui.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTarget(Request $request, $id)
    {
        $request->validate([
            'target_bulanan' => 'required|integer|min:1'
        ]);

        DB::table('users')
            ->where('id', $id)
            ->where('role', 'salesman')
            ->update(['target_bulanan' => $request->target_bulanan]);

        return response()->json([
            'status' => 'success',
            'message' => 'Target operasional bulanan berhasil diperbarui.'
        ], 200);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // 1. Lepaskan relasi di tabel toko agar tidak terjadi error Foreign Key Constraint
            // Toko yang dulunya dipegang salesman ini akan kembali menjadi 'null' (belum ada salesman)
            DB::table('toko')->where('id_salesman', $id)->update(['id_salesman' => null]);

            // 2. Hapus data salesman dari tabel users
            $deleted = DB::table('users')
                ->where('id', $id)
                ->where('role', 'salesman')
                ->delete();

            if (!$deleted) {
                throw new \Exception("Data salesman tidak ditemukan.");
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Salesman berhasil dihapus secara permanen.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus salesman: ' . $e->getMessage()
            ], 500);
        }
    }
}