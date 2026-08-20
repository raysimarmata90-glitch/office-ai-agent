<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pekerjaan extends Model
{
    protected $table = 'pekerjaan';

    protected $fillable = [
        'user_id',
        'name',
        'division',
        'nama_projek',
        'pekerjaan',
        'status',
        'kategori',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
