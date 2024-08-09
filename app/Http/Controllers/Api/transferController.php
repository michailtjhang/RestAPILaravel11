<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Transfer;
use App\Models\User;

class transferController extends Controller
{
    public function transfer(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'target_user' => 'required|uuid|exists:users,id',
            'amount' => 'required|integer|min:1',
            'remarks' => 'required|string|max:255',
        ]);

        // Jika validasi gagal
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

        // Mengecek saldo pengguna sebelum melakukan transfer
        $balanceBefore = $user->balance ?? 0;

        if ($balanceBefore < $request->amount) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Balance is not enough',
            ], 400);
        }

        // Hitung saldo setelah transfer
        $balanceAfter = $balanceBefore - $request->amount;

        // Mendapatkan pengguna tujuan transfer
        $targetUser = User::find($request->target_user);

        if (!$targetUser) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Target user not found',
            ], 404);
        }

        try {
            // Simpan data transfer ke database
            $transfer = transfer::create([
                'amount'        => $request->amount,
                'remarks'       => $request->remarks,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'user_id'       => $user->id,
            ]);

            // Update saldo pengguna
            $user->balance = $balanceAfter;
            $user->save();

            // Tambahkan saldo ke target user
            $targetUser->balance += $request->amount;
            $targetUser->save();

            // Response success
            return response()->json([
                'status' => 'SUCCESS',
                'result' => [
                    'transfer_id'    => $transfer->id,
                    'amount'         => $transfer->amount,
                    'remarks'        => $transfer->remarks,
                    'balance_before' => $transfer->balance_before,
                    'balance_after'  => $transfer->balance_after,
                    'created_date'   => $transfer->created_at->format('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Exception $e) {
            // Jika terjadi error saat menyimpan ke database
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to process transfer, please try again later.',
            ], 500);
        }
    }
}
