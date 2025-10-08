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

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/notifications/bulk-delete', [NotificationController::class, 'bulkDestroy']);

    Route::post('/save-push-token', [NotificationController::class, 'savePushToken'])
        ->withoutMiddleware('throttle:api');

    Route::post('/delete-push-token', [NotificationController::class, 'deleteOwnToken'])
        ->withoutMiddleware('throttle:api');

    Route::middleware('can:isAdmin')->group(function () {
        Route::apiResource('/karani', KaraniController::class);
        Route::post('karani/{id}/reset-password', [KaraniController::class, 'resetPassword']);
        Route::apiResource('/kota', KotaController::class);

        Route::post('/update-barang/{id}/approve', [RequestUpdateBarangController::class, 'approve']);
        Route::post('/update-barang/{id}/reject',  [RequestUpdateBarangController::class, 'reject']);
        Route::post('/barang/bulk-delete', [BarangController::class, 'bulkDestroy']);
    });

    Route::get('/kota', [KotaController::class, 'index']);
    Route::apiResource('/barang', BarangController::class);
    Route::get('/barang/by-kode/{kode}', [BarangController::class, 'findByKode']);
    Route::apiResource('/update-barang', RequestUpdateBarangController::class);
    Route::post('/update-barang/bulk-delete', [RequestUpdateBarangController::class, 'bulkDestroy']);

    Route::put('/barang/{id}/update-status', [BarangController::class, 'terimaBarang']);
    Route::put('/barang/{id}/batalkan', [BarangController::class, 'batalkan']);
    Route::post('/barang/export-supir', [BarangController::class, 'exportSupir']);
});
