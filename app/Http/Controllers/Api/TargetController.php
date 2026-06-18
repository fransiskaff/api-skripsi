<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; // Sesuaikan dengan model Salesman Anda
use App\Models\SalesTarget;

class TargetController extends Controller
{
    // Mengambil daftar salesman untuk ditampilkan di form React
    public function getSalesmen()
    {
        // Ambil user yang rolenya 'sales' (sesuaikan dengan logic Anda)
        $salesmen = User::where('role', 'sales')->get(['id', 'name']);
        return response()->json($salesmen);
    }

    // Menyimpan target secara massal (Bulk Save)
    public function storeBulk(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'targets' => 'required|array',
            'targets.*.salesman_id' => 'required|exists:users,id',
            'targets.*.target_amount' => 'required|numeric'
        ]);

        foreach ($request->targets as $target) {
            // updateOrCreate akan mengecek: jika bulan & tahun & salesman_id sama, maka update. Jika tidak, create baru.
            SalesTarget::updateOrCreate(
                [
                    'salesman_id' => $target['salesman_id'],
                    'month' => $request->month,
                    'year' => $request->year
                ],
                [
                    'target_amount' => $target['target_amount']
                ]
            );
        }

        return response()->json(['message' => 'Target penjualan berhasil disimpan!']);
    }
}