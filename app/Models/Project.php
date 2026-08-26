<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'nama',
        'warna',
        'deskripsi',
        'berisiko',
        'mulai',
        'selesai',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'berisiko' => 'boolean',
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    /**
     * Palet warna penanda proyek. Sengaja berjarak jauh satu sama lain supaya
     * dua proyek yang berdampingan di daftar tidak terlihat serupa.
     */
    public const PALET = [
        '#f55d14', '#2c5cc5', '#1f7a52', '#b23c35',
        '#6b46c1', '#0e7490', '#a05a1c', '#be185d',
        '#4d7c0f', '#0369a1', '#c026d3', '#475569',
        '#b45309', '#047857', '#7e22ce', '#1e40af',
    ];

    protected static function booted(): void
    {
        // Proyek baru selalu dapat warna, tanpa perlu diisi pemanggilnya.
        static::creating(function (self $p) {
            if (blank($p->warna)) {
                $p->warna = self::warnaBerikutnya();
            }
        });
    }

    /**
     * Warna palet yang paling jarang dipakai proyek lain. Selama jumlah proyek
     * belum melewati panjang palet, hasilnya selalu unik.
     */
    public static function warnaBerikutnya(): string
    {
        $dipakai = self::query()->pluck('warna')->countBy();

        return collect(self::PALET)
            ->sortBy(fn ($w) => $dipakai->get($w, 0))
            ->first();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jumlahTugas(): int
    {
        return $this->tasks->count();
    }

    public function jumlahSelesai(): int
    {
        return $this->tasks->where('status', Task::STATUS_DONE)->count();
    }

    public function persentase(): int
    {
        $total = $this->jumlahTugas();

        return $total === 0 ? 0 : (int) round($this->jumlahSelesai() / $total * 100);
    }

    public function kontributor(): int
    {
        return $this->tasks->pluck('user_id')->unique()->count();
    }
}
