<?php

namespace App\Http\Controllers;

class StorageLinkController extends Controller
{
    public function link()
    {
        $source = storage_path('app/public');
        $destination = public_path('storage');

        // Jika folder public/storage belum ada, buat
        if (!file_exists($destination)) {
            mkdir($destination, 0775, true);
        }

        // Copy seluruh isi folder storage/app/public ke public/storage
        $this->copyDirectory($source, $destination);

        return "Storage folder berhasil dicopy ke public/storage (fallback mode).";
    }

    private function copyDirectory($source, $destination)
    {
        $dir = opendir($source);
        @mkdir($destination);

        while (false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..') {
                if (is_dir($source . '/' . $file)) {
                    $this->copyDirectory($source . '/' . $file, $destination . '/' . $file);
                } else {
                    copy($source . '/' . $file, $destination . '/' . $file);
                }
            }
        }

        closedir($dir);
    }
}
