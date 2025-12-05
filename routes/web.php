<?php

use App\Http\Controllers\StorageLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'flores travel';
});

Route::get('/make-storage-link', [StorageLinkController::class, 'link']);