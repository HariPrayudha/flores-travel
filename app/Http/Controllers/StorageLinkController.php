<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StorageLinkController extends Controller
{
    public function link()
    {
        $target = storage_path('app/public');
        $link = public_path('storage');

        // Jika sudah ada folder storage di public_html, hapus dulu
        if (file_exists($link)) {
            return "Storage link sudah ada (public/storage sudah tersedia).";
        }

        // Coba buat symlink
        try {
            symlink($target, $link);
        } catch (\Exception $e) {
            return "Gagal membuat symlink: " . $e->getMessage();
        }

        // Verifikasi apakah symlink berhasil
        if (is_link($link)) {
            return "Storage link berhasil dibuat! 🎉<br>Path: $link";
        }

        return "Symlink gagal dibuat (mungkin fungsi symlink diblokir server).";
    }
}
