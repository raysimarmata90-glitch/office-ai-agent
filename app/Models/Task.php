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

    public static function warnaStatus(string $status): array
    {
        return match ($status) {
            self::STATUS_DONE => ['bg' => '#dcefe6', 'text' => '#1f7a52'],
            self::STATUS_IN_PROGRESS => ['bg' => '#fdeadb', 'text' => '#a05a1c'],
            self::STATUS_REVIEW => ['bg' => '#dde9fd', 'text' => '#2c5cc5'],
            self::STATUS_BLOCKED => ['bg' => '#fde3e1', 'text' => '#b23c35'],
            default => ['bg' => '#eef0f6', 'text' => '#5b6172'],
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
