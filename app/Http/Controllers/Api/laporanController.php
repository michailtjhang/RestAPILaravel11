<?php

namespace App\Http\Controllers\Api;

use App\Models\topup;
use App\Models\payment;
use App\Models\transfer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class laporanController extends Controller
{
    public function getTransactions(Request $request)
    {
        // Mendapatkan user yang sedang login
        $user = Auth::user();

        // Jika user tidak ditemukan (misalnya karena token tidak valid)
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Unauthenticated',
            ], 401);
        }

        try {
            // Mengambil data transfer
            $transfers = transfer::where('user_id', $user->id)
                ->get(['id as transaction_id', 'amount', 'remarks', 'balance_before', 'balance_after', 'created_at'])
                ->map(function ($transfer) {
                    return [
                        'transaction_id' => $transfer->transaction_id,
                        'status' => 'SUCCESS',
                        'user_id' => Auth::id(),
                        'transaction_type' => 'DEBIT',
                        'amount' => $transfer->amount,
                        'remarks' => $transfer->remarks,
                        'balance_before' => $transfer->balance_before,
                        'balance_after' => $transfer->balance_after,
                        'created_date' => $transfer->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // Mengambil data payment
            $payments = payment::where('user_id', $user->id)
                ->get(['id as transaction_id', 'amount', 'remarks', 'balance_before', 'balance_after', 'created_at'])
                ->map(function ($payment) {
                    return [
                        'transaction_id' => $payment->transaction_id,
                        'status' => 'SUCCESS',
                        'user_id' => Auth::id(),
                        'transaction_type' => 'DEBIT',
                        'amount' => $payment->amount,
                        'remarks' => $payment->remarks,
                        'balance_before' => $payment->balance_before,
                        'balance_after' => $payment->balance_after,
                        'created_date' => $payment->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // Mengambil data topup
            $topups =topup::where('user_id', $user->id)
                ->get(['top_up_id as transaction_id', 'amount_top_up as amount', 'balance_before', 'balance_after', 'created_at'])
                ->map(function ($topup) {
                    return [
                        'transaction_id' => $topup->transaction_id,
                        'status' => 'SUCCESS',
                        'user_id' => Auth::id(),
                        'transaction_type' => 'CREDIT',
                        'amount' => $topup->amount,
                        'remarks' => '',
                        'balance_before' => $topup->balance_before,
                        'balance_after' => $topup->balance_after,
                        'created_date' => $topup->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            // Menggabungkan semua transaksi
            $transactions = $transfers->merge($payments)->merge($topups)->sortByDesc('created_date')->values();

            // Response success
            return response()->json([
                'status' => 'SUCCESS',
                'result' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            // Jika terjadi error saat mengambil data
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to retrieve transactions, please try again later.',
            ], 500);
        }
    }
}
