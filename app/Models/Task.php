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
        'objektif',
        'harapan',
        'deliverable',
        'detail',
        'progress_text',
        'progress_percentage',
        'estimasi',
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

    /**
     * Ekstrak persentase progress dari text progress user.
     * Mendukung berbagai format input seperti:
     * - "50%" atau "50 persen"
     * - "setengah jalan", "hampir selesai"
     * - "baru mulai", "masih awal"
     * - "sudah selesai 80%"
     * 
     * @param string|null $progressText
     * @return int Progress dalam persen (0-100), default 0 jika tidak bisa diekstrak
     */
    public static function extractProgressPercentage(?string $progressText): int
    {
        if (empty($progressText)) {
            return 0;
        }

        $text = mb_strtolower(trim($progressText));

        // Cek explicit percentage (50%, 75 persen, dll)
        if (preg_match('/(\d{1,3})\s*(?:%|persen)/i', $text, $matches)) {
            return min(100, max(0, (int) $matches[1]));
        }

        // Cek angka desimal (0.5, 0.75, dll)
        if (preg_match('/0[.,](\d+)/', $text, $matches)) {
            $decimal = (float) ('0.' . $matches[1]);
            return min(100, max(0, (int) round($decimal * 100)));
        }

        // Mapping keyword ke persentase estimasi
        $keywords = [
            // Belum mulai / baru mulai
            '/belum\s+mulai|belum\s+dikerjakan|belum\s+dimulai/' => 0,
            '/baru\s+mulai|baru\s+memulai|baru\s+dimulai|masih\s+awal|tahap\s+awal/' => 10,
            
            // Progress rendah
            '/sedikit|masih\s+sedikit|baru\s+sedikit|kategori\s+easy|baru\s+mengerjakan/' => 25,
            
            // Progress sedang
            '/seperempat|25%|1\/4/' => 25,
            '/setengah|separuh|50%|1\/2|pertengahan/' => 50,
            '/sebagian\s+besar|hampir\s+setengah/' => 40,
            
            // Progress tinggi
            '/hampir\s+selesai|nyaris\s+selesai|tinggal\s+sedikit|mendekati\s+selesai/' => 85,
            '/hampir\s+rampung|hampir\s+beres/' => 90,
            
            // Selesai
            '/selesai|rampung|tuntas|beres|sudah\s+selesai|done|complete/' => 100,
        ];

        foreach ($keywords as $pattern => $percentage) {
            if (preg_match($pattern, $text)) {
                return $percentage;
            }
        }

        // Default: jika tidak bisa di-parse, return 0
        return 0;
    }
}
