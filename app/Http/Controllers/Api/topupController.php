<?php

namespace App\Http\Controllers\Api;

use App\Models\topup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class topupController extends Controller
{
    public function topup(Request $request)
    {
        // Validasi input
        // Validasi input
        $validator = Validator::make($request->all(), [
            'amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        // Mendapatkan user yang sedang login
        $user = User::find(Auth::user()->id);

        // Jika user tidak ditemukan (misalnya karena token tidak valid)
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Hitung saldo sebelum dan sesudah topup
        $balanceBefore = $user->balance ?? 0;  // Asumsi user memiliki kolom balance di database
        $balanceAfter = $balanceBefore + $request->amount;

        try {
            // Simpan data topup ke database
            $topup = topup::create([
                'amount_top_up'  => $request->amount,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'user_id'        => $user->id,
            ]);

            // Update saldo user
            $user->balance = $balanceAfter;
            $user->save();

            // Response success
            return response()->json([
                'status' => 'SUCCESS',
                'result' => [
                    'top_up_id'      => $topup->id,
                    'amount_top_up'  => $topup->amount_top_up,
                    'balance_before' => $topup->balance_before,
                    'balance_after'  => $topup->balance_after,
                    'created_date'   => $topup->created_at->format('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Exception $e) {
            // Jika terjadi error saat menyimpan ke database atau update saldo
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to process topup, please try again later.',
            ], 500);
        }
    }
}
