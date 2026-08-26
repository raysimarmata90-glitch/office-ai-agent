<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\Waktu;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'title',
        'status',
        'current_step',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('step_number');
    }

    public function pesanTerakhirUser(): HasOne
    {
        // Syarat tambahan harus lewat closure ofMany(); `where()` sebelum
        // latestOfMany() tidak ikut terbawa ke subquery agregatnya.
        return $this->hasOne(Message::class)->ofMany(
            ['id' => 'max'],
            fn ($q) => $q->where('sender_type', 'user')
        );
    }

    /**
     * Jawaban user terakhir yang cukup panjang untuk jadi keterangan tugas.
     * Ambangnya sengaja tinggi supaya jawaban singkat seperti "Iya" atau
     * "Tidak, hanya ini saja" terlewati dan yang terpilih adalah deskripsi
     * pekerjaannya.
     */
    public function pesanDetail(): HasOne
    {
        return $this->hasOne(Message::class)->ofMany(
            ['id' => 'max'],
            fn ($q) => $q->where('sender_type', 'user')->whereRaw('LENGTH(content) >= 40')
        );
    }

    public function chatHistories(): HasMany
    {
        return $this->hasMany(ChatHistory::class)->orderBy('created_at');
    }

    /**
     * Nama proyek untuk baris pertama daftar riwayat.
     */
    public function namaProyek(): string
    {
        $judul = trim((string) $this->title);

        if ($judul === '' || in_array(strtolower($judul), ['new chat', 'percakapan baru'], true)) {
            return 'Percakapan Baru';
        }

        if (Str::startsWith($judul, ['Proyek: ', 'Project: '])) {
            $judul = trim(Str::after($judul, ': '));
        }

        return Str::limit($judul, 22);
    }

    /**
     * Judul tugas untuk baris kedua daftar riwayat.
     */
    public function judulTugas(): string
    {
        $pesan = trim((string) ($this->pesanDetail?->content ?: $this->pesanTerakhirUser?->content));

        return $pesan === ''
            ? 'Belum ada detail tugas'
            : Str::limit(preg_replace('/\s+/', ' ', $pesan), 34);
    }

    public function waktuRingkas(): string
    {
        return Waktu::ringkas($this->updated_at);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function incrementStep(): void
    {
        $this->increment('current_step');
    }
}
