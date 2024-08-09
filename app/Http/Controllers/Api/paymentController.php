<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class paymentController extends Controller
{
    public function pay(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
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

        // Mengecek saldo pengguna sebelum melakukan pembayaran
        $balanceBefore = $user->balance ?? 0;

        if ($balanceBefore < $request->amount) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Balance is not enough',
            ], 400);
        }

        // Hitung saldo setelah pembayaran
        $balanceAfter = $balanceBefore - $request->amount;

        try {
            // Simpan data pembayaran ke database
            $payment = payment::create([
                'amount'        => $request->amount,
                'remarks'       => $request->remarks,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'user_id'       => $user->id,
            ]);

            // Update saldo pengguna
            $user->balance = $balanceAfter;
            $user->save();

            // Response success
            return response()->json([
                'status' => 'SUCCESS',
                'result' => [
                    'payment_id'    => $payment->id,
                    'amount'        => $payment->amount,
                    'remarks'       => $payment->remarks,
                    'balance_before' => $payment->balance_before,
                    'balance_after'  => $payment->balance_after,
                    'created_date'  => $payment->created_at->format('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Exception $e) {
            // Jika terjadi error saat menyimpan ke database
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to process payment, please try again later.',
            ], 500);
        }
    }
}
