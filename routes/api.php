<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\KaraniController;
use App\Http\Controllers\KotaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RequestUpdateBarangController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('can:isAdmin')->group(function () {
        Route::apiResource('/karani', KaraniController::class);
        Route::post('karani/{id}/reset-password', [KaraniController::class, 'resetPassword']);
        Route::apiResource('/kota', KotaController::class);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::post('/save-push-token', [NotificationController::class, 'savePushToken']);
    });

    Route::get('/kota', [KotaController::class, 'index']);
    Route::apiResource('/barang', BarangController::class);
    Route::apiResource('/update-barang', RequestUpdateBarangController::class);

    Route::put('/barang/{id}/update-status', [BarangController::class, 'terimaBarang']);
});
