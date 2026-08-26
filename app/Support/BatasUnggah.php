<?php

namespace App\Support;

class BatasUnggah
{
    /** Batas yang diinginkan aplikasi (KB). */
    public const TARGET_KB = 10240;

    /** Ekstensi evidence yang diterima. */
    public const EKSTENSI = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'webp', 'html', 'md'];

    /**
     * Batas ukuran per file (KB) — diambil dari nilai terkecil antara
     * target aplikasi, upload_max_filesize, dan post_max_size PHP.
     * Tanpa ini, file di atas batas PHP gagal diam-diam dengan pesan
     * "failed to upload" yang membingungkan.
     */
    public static function maksKb(): int
    {
        $kandidat = array_filter([
            self::TARGET_KB,
            self::iniKeKb('upload_max_filesize'),
            self::iniKeKb('post_max_size'),
        ]);

        return (int) max(128, min($kandidat));
    }

    public static function maksMb(): float
    {
        return round(self::maksKb() / 1024, 1);
    }

    public static function accept(): string
    {
        return '.' . implode(',.', self::EKSTENSI);
    }

    private static function iniKeKb(string $kunci): ?int
    {
        $nilai = trim((string) ini_get($kunci));

        if ($nilai === '' || $nilai === '-1' || $nilai === '0') {
            return null;
        }

        $satuan = strtolower(substr($nilai, -1));
        $angka = (float) $nilai;

        $bytes = match ($satuan) {
            'g' => $angka * 1024 * 1024 * 1024,
            'm' => $angka * 1024 * 1024,
            'k' => $angka * 1024,
            default => $angka,
        };

        return (int) floor($bytes / 1024);
    }
}
