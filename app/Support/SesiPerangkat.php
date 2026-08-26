<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Daftar sesi login milik seorang pengguna, lengkap dengan tebakan perangkat
 * dan peramban dari user agent. Dipakai modal profil (pengguna sendiri) dan
 * modal ubah pengguna di halaman Tim (admin melihat sesi orang lain).
 */
class SesiPerangkat
{
    /** @return array<int,array<string,mixed>> terbaru lebih dulu */
    public static function daftar(int $userId, ?string $sesiIni = null): array
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) use ($sesiIni) {
                $waktu = Carbon::createFromTimestamp($s->last_activity);

                return [
                    'id' => $s->id,
                    'ini' => $sesiIni !== null && $s->id === $sesiIni,
                    'ip' => $s->ip_address ?: '–',
                    'perangkat' => self::perangkat($s->user_agent),
                    'jenis' => self::jenis($s->user_agent),
                    'peramban' => self::peramban($s->user_agent),
                    'terakhir' => $waktu->translatedFormat('d M Y, H:i'),
                    'lalu' => $waktu->diffForHumans(),
                ];
            })
            ->values()
            ->all();
    }

    /** Kunci ikon perangkat untuk tampilan sesi aktif. */
    public static function jenis(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'apple-mobile',
            str_contains($ua, 'Android') => 'android',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS') => 'apple',
            str_contains($ua, 'Windows') => 'windows',
            str_contains($ua, 'Linux') => 'linux',
            default => 'lain',
        };
    }

    public static function perangkat(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Perangkat lain',
        };
    }

    public static function peramban(?string $ua): string
    {
        $ua = (string) $ua;

        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            $ua === '' => 'Tidak diketahui',
            default => 'Peramban lain',
        };
    }
}
