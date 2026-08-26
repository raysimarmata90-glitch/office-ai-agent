<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'department_id',
        'display_name',
        'description',
    ];

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->name === 'admin';
    }

    public function isUser(): bool
    {
        return $this->name === 'user';
    }
}
