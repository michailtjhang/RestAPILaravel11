<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class userController extends Controller
{
    public function register(Request $request)
    {
        // Validasi data yang dikirim
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|unique:users,phone_number',
            'address'    => 'required|string|max:255',
            'pin'        => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            // Membuat user baru
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'phone_number' => $request->phone_number,
                'address'    => $request->address,
                'pin'        => Hash::make($request->pin),
            ]);

            return response()->json([
                'status' => 'SUCCESS',
                'result' => [
                    'user_id'     => $user->id,
                    'first_name'  => $user->first_name,
                    'last_name'   => $user->last_name,
                    'phone_number' => $user->phone_number,
                    'address'     => $user->address,
                    'created_date' => $user->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);
        } catch (QueryException $e) {
            // Jika terjadi error saat menyimpan ke database
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to register user, please try again later.'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        // Validasi data yang dikirim
        $request->validate([
            'phone_number' => 'required|string',
            'pin' => 'required|string',
        ]);

        // Cari user berdasarkan nomor telepon
        $user = User::where('phone_number', $request->phone_number)->first();

        // Jika user tidak ditemukan atau PIN tidak sesuai
        if (!$user || !Hash::check($request->pin, $user->pin)) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Phone number and pin doesn’t match.'
            ], 401);
        }

        try {
            // Jika login berhasil, buat token
            $accessToken = $user->createToken('access_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token')->plainTextToken;

            return response()->json([
                'status' => 'SUCCESS',
                'result' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                ]
            ], 200);
        } catch (\Exception $e) {
            // Jika terjadi error saat pembuatan token
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to generate tokens, please try again later.'
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        // Mendapatkan user yang sedang login
        $user = User::find(Auth::user()->id);

        // Jika user tidak ditemukan (misalnya karena token tidak valid)
        if (!$user) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'status' => 'ERROR',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        try {
            // Update profil pengguna
            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'address' => $request->address,
            ]);

            // Response success
            return response()->json([
                'status' => 'SUCCESS',
                'result' => [
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'address' => $user->address,
                    'updated_date' => now()->format('Y-m-d H:i:s'),
                ]
            ], 200);
        } catch (\Exception $e) {
            // Jika terjadi error saat update data
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Failed to update profile, please try again later.',
            ], 500);
        }
    }
}
