<?php

namespace App\Models;

use App\Support\Waktu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    public const STATUS_TO_DO = 'to_do';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_DONE = 'done';

    protected $fillable = [
        'judul',
        'deskripsi',
        'project_id',
        'user_id',
        'reviewer_id',
        'created_by',
        'status',
        'prioritas',
        'mulai',
        'selesai',
        'selesai_pada',
    ];

    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
            'selesai_pada' => 'datetime',
        ];
    }

    public static function daftarStatus(): array
    {
        return [
            self::STATUS_TO_DO => 'To Do',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_REVIEW => 'Review',
            self::STATUS_BLOCKED => 'Blocked',
            self::STATUS_DONE => 'Done',
        ];
    }

    /**
     * Urutan status untuk bar komposisi dan legendanya: yang sudah selesai
     * ditaruh paling depan, lalu mundur sampai yang belum dikerjakan.
     */
    public static function daftarStatusSelesaiDulu(): array
    {
        return array_reverse(self::daftarStatus(), true);
    }

    /**
     * Palet status: lima hue yang berjauhan (netral, biru, ungu, merah mawar, hijau)
     * supaya titik status di kanban, timeline, dan badge tetap mudah dibedakan.
     * Sengaja menghindari oranye agar tidak tertukar dengan warna merek.
     */
    public static function warnaStatus(string $status): array
    {
        return match ($status) {
            self::STATUS_DONE => ['bg' => '#d6f0e5', 'text' => '#047857'],
            self::STATUS_IN_PROGRESS => ['bg' => '#dce7fd', 'text' => '#1d4ed8'],
            self::STATUS_REVIEW => ['bg' => '#f1e3fd', 'text' => '#7e22ce'],
            self::STATUS_BLOCKED => ['bg' => '#fde3ea', 'text' => '#be123c'],
            default => ['bg' => '#e8edf4', 'text' => '#475569'],
        };
    }

    /** Prioritas beserta warna titiknya, dipakai pilihan pada form tugas. */
    public static function daftarPrioritas(): array
    {
        return [
            'Tinggi' => self::warnaPrioritas('Tinggi')['text'],
            'Sedang' => self::warnaPrioritas('Sedang')['text'],
            'Rendah' => self::warnaPrioritas('Rendah')['text'],
        ];
    }

    /**
     * Warna badge prioritas — dibedakan dari palet status supaya keduanya
     * bisa berdampingan pada satu kartu kanban tanpa saling tertukar.
     */
    public static function warnaPrioritas(?string $prioritas): array
    {
        return match ($prioritas) {
            'Tinggi' => ['bg' => '#ffe4e6', 'text' => '#9f1239'],
            'Rendah' => ['bg' => '#dce7fd', 'text' => '#1d4ed8'],
            default => ['bg' => '#fef3c7', 'text' => '#92400e'],
        };
    }

    /**
     * Warna titik status untuk header kolom kanban.
     */
    public static function titikStatus(string $status): string
    {
        return self::warnaStatus($status)['text'];
    }

    /**
     * Waktu aktivitas terakhir dalam format ringkas untuk kartu kanban.
     */
    public function waktuSingkat(): string
    {
        return Waktu::singkat($this->updated_at);
    }

    public function statusLabel(): string
    {
        return self::daftarStatus()[$this->status] ?? $this->status;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(TaskEvidence::class);
    }
}
