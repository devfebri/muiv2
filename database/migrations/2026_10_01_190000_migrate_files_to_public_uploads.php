<?php

use App\Models\Berita;
use App\Models\Fatwa;
use App\Models\Surat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat direktori upload di public/
        $dirs = [
            public_path('uploads/berita'),
            public_path('uploads/fatwa'),
            public_path('uploads/surat'),
        ];

        foreach ($dirs as $dir) {
            File::ensureDirectoryExists($dir);
        }

        // 2. Salin file dari storage/app/public ke public/uploads jika ada
        $copyMapping = [
            storage_path('app/public/berita') => public_path('uploads/berita'),
            storage_path('app/public/fatwa') => public_path('uploads/fatwa'),
            storage_path('app/public/surat') => public_path('uploads/surat'),
        ];

        foreach ($copyMapping as $sourceDir => $targetDir) {
            if (File::isDirectory($sourceDir)) {
                $files = File::files($sourceDir);
                foreach ($files as $file) {
                    $targetFile = $targetDir.DIRECTORY_SEPARATOR.$file->getFilename();
                    if (! File::exists($targetFile)) {
                        File::copy($file->getPathname(), $targetFile);
                    }
                }
            }
        }

        // 3. Bersihkan data di database agar HANYA menyimpan nama file (tanpa path/url)
        foreach (Berita::whereNotNull('gambar')->cursor() as $berita) {
            $cleaned = basename($berita->gambar);
            if ($cleaned !== $berita->gambar) {
                $berita->updateQuietly(['gambar' => $cleaned]);
            }
        }

        foreach (Fatwa::whereNotNull('filepdf')->cursor() as $fatwa) {
            $cleaned = basename($fatwa->filepdf);
            if ($cleaned !== $fatwa->filepdf) {
                $fatwa->updateQuietly(['filepdf' => $cleaned]);
            }
        }

        foreach (Surat::whereNotNull('file_surat')->cursor() as $surat) {
            $cleaned = basename($surat->file_surat);
            if ($cleaned !== $surat->file_surat) {
                $surat->updateQuietly(['file_surat' => $cleaned]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
