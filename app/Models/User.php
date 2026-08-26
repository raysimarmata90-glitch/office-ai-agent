<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'department_id',
        'phone',
        'bio',
        'foto',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }


    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function reviewTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'reviewer_id');
    }

    /**
     * URL foto profil, null bila belum diunggah.
     *
     * Sengaja relatif terhadap root: Storage::url() menempelkan APP_URL, yang
     * kerap berbeda dengan host yang sedang dipakai (mis. port dev), sehingga
     * gambarnya gagal dimuat.
     */
    public function fotoUrl(): ?string
    {
        return $this->foto ? '/storage/' . ltrim($this->foto, '/') : null;
    }

    /** Nama jabatan untuk ditampilkan di profil dan sidebar. */
    public function namaRole(): string
    {
        return $this->role?->display_name ?: ($this->role?->name ?: 'Pengguna');
    }

    /**
     * Warna avatar yang tetap sama untuk satu pengguna (dikunci dari id-nya),
     * dipakai di kartu kanban dan daftar reviewer.
     */
    public function warnaAvatar(): array
    {
        return self::paletAvatar($this->id);
    }

    public static function paletAvatar(?int $id): array
    {
        $palet = [
            ['bg' => '#dce7fd', 'text' => '#1d4ed8'],
            ['bg' => '#d6f0e5', 'text' => '#047857'],
            ['bg' => '#f1e3fd', 'text' => '#7e22ce'],
            ['bg' => '#fde3ea', 'text' => '#be123c'],
            ['bg' => '#fdeadb', 'text' => '#b45309'],
            ['bg' => '#e0f2fe', 'text' => '#0369a1'],
            ['bg' => '#e8edf4', 'text' => '#475569'],
            ['bg' => '#fce7f3', 'text' => '#be185d'],
        ];

        return $palet[($id ?? 0) % count($palet)];
    }

    public function inisial(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first . $last);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role->name === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role->name === 'user';
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }
}
