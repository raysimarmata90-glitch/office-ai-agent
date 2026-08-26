<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class Waktu
{
    /**
     * Format waktu ringkas untuk kartu & daftar:
     * - hari ini  -> "17:49"
     * - hari lain -> "25/08, 17:49"
     */
    public static function singkat(?Carbon $waktu): string
    {
        if (! $waktu) {
            return '–';
        }

        return $waktu->isToday()
            ? $waktu->format('H:i')
            : $waktu->format('d/m') . ', ' . $waktu->format('H:i');
    }

    /**
     * Versi paling ringkas untuk pojok kanan daftar riwayat:
     * - hari ini  -> "18:02"
     * - hari lain -> "25/08"
     */
    public static function ringkas(?Carbon $waktu): string
    {
        if (! $waktu) {
            return '';
        }

        return $waktu->isToday() ? $waktu->format('H:i') : $waktu->format('d/m');
    }
}
