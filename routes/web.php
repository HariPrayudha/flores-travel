<?php

use App\Http\Controllers\StorageLinkController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return 'flores travel';
});

Route::get('/storage/{path}', function ($path) {
    $file = Storage::disk('public')->path($path);

    if (!File::exists($file)) {
        abort(404);
    }

    return response()->file($file);
})->where('path', '.*');
