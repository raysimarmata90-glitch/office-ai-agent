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
