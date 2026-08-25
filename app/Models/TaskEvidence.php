<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEvidence extends Model
{
    protected $table = 'task_evidences';

    protected $fillable = [
        'task_id',
        'uploaded_by',
        'nama_file',
        'path',
        'mime',
        'ukuran',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isGambar(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
