<?php

use Illuminate\Http\Request;

use App\Http\Controllers\Api\laporanController;
use App\Http\Controllers\Api\paymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\userController;
use App\Http\Controllers\Api\topupController;
use App\Http\Controllers\Api\transferController;

Route::post('/register', [userController::class, 'register']);
Route::post('/login', [userController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/topup', [topupController::class, 'topup']);
    Route::post('/pay', [paymentController::class, 'pay']);
    Route::post('/transfer', [transferController::class, 'transfer']);
    Route::get('/laporan', [laporanController::class, 'getTransactions']);
    Route::put('/profile', [userController::class, 'updateProfile']);
});
