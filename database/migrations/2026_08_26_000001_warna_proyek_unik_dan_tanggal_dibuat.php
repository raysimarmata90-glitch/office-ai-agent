<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dua perbaikan data:
 * 1. Proyek yang dibuat di luar seeder semuanya memakai warna default yang sama,
 *    sehingga penanda warnanya tidak bisa dibedakan. Warna dibagi ulang dari palet.
 * 2. Seeder menghitung created_at dari tanggal mulai tugas, jadi tugas yang
 *    dijadwalkan ke depan tercatat "dibuat" di masa depan. Ditarik ke masa lalu
 *    tanpa mengubah urutannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $palet = Project::PALET;

        Project::orderBy('id')->get()->each(function (Project $p, int $i) use ($palet) {
            $p->forceFill(['warna' => $palet[$i % count($palet)]])->saveQuietly();
        });

        $sekarang = now();

        DB::table('tasks')->where('created_at', '>', $sekarang)->orderBy('created_at')
            ->get(['id', 'created_at', 'updated_at'])
            ->values()
            ->each(function ($t, int $i) use ($sekarang) {
                // Urutan lama dipertahankan: makin jauh di masa depan, makin baru.
                $baru = $sekarang->copy()->subMinutes(($i + 1) * 37);
                DB::table('tasks')->where('id', $t->id)->update([
                    'created_at' => $baru,
                    'updated_at' => $baru->copy()->addMinutes(12),
                ]);
            });
    }

    public function down(): void
    {
        // Perbaikan data, tidak dikembalikan.
    }
};
