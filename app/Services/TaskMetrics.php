<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class TaskMetrics
{
    public static function ringkasStatus(Collection $tasks): array
    {
        $total = $tasks->count();
        $done = $tasks->where('status', Task::STATUS_DONE)->count();
        $progress = $tasks->whereIn('status', [Task::STATUS_IN_PROGRESS, Task::STATUS_REVIEW])->count();
        $todo = $total - $done - $progress;

        return [
            'total' => $total,
            'done' => $done,
            'progress' => $progress,
            'todo' => max(0, $todo),
            'pct' => $total === 0 ? 0 : (int) round($done / $total * 100),
            'pctDone' => $total === 0 ? 0 : $done / $total * 100,
            'pctProgress' => $total === 0 ? 0 : $progress / $total * 100,
            'pctTodo' => $total === 0 ? 0 : max(0, $todo) / $total * 100,
        ];
    }

    /**
     * Komposisi status satu kumpulan tugas: seluruh status dijumlahkan menjadi
     * 100% dari total kumpulan itu sendiri — bukan total global — sehingga sisa
     * abu-abu hanya muncul kalau memang belum ada tugas. Urutannya dimulai dari
     * yang sudah selesai.
     */
    public static function segmen($items): array
    {
        $total = $items->count();

        return collect(Task::daftarStatusSelesaiDulu())
            ->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'jumlah' => $items->where('status', $key)->count(),
                'warna' => Task::titikStatus($key),
                'w' => $total ? $items->where('status', $key)->count() / $total * 100 : 0,
            ])
            ->values()
            ->all();
    }

    public static function kanban(Collection $tasks): array
    {
        $kolom = [];
        foreach (Task::daftarStatus() as $key => $label) {
            $items = $tasks->where('status', $key)->values();
            $kolom[] = [
                'key' => $key,
                'nama' => $label,
                'count' => $items->count(),
                'items' => $items,
            ];
        }

        return $kolom;
    }

    public static function bulanTimeline(Collection $tasks, int $jumlahBulan = 6): array
    {
        $mulai = $tasks->filter(fn ($t) => $t->mulai)->min('mulai');
        $awal = $mulai ? $mulai->copy()->startOfMonth() : now()->startOfMonth();

        $bulan = [];
        for ($i = 0; $i < $jumlahBulan; $i++) {
            $bulan[] = $awal->copy()->addMonths($i);
        }

        return $bulan;
    }

    public static function posisiBar($mulai, $selesai, array $bulan): ?array
    {
        if (! $mulai || ! $selesai || $bulan === []) {
            return null;
        }

        $awal = $bulan[0]->copy()->startOfMonth();
        $akhir = end($bulan)->copy()->endOfMonth();
        $rentang = $awal->diffInDays($akhir);

        if ($rentang <= 0) {
            return null;
        }

        $s = max(0, $awal->diffInDays($mulai, false));
        $e = min($rentang, $awal->diffInDays($selesai, false));

        if ($e <= $s) {
            $e = $s + 1;
        }

        return [
            'left' => round($s / $rentang * 100, 2),
            'width' => round(max(1, $e - $s) / $rentang * 100, 2),
        ];
    }
}
