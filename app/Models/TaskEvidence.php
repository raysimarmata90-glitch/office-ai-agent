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

    /**
     * Simpan berkas unggahan sebagai evidence sebuah tugas.
     * Dipakai form tugas milik user maupun form assign/ubah milik admin.
     *
     * @param  iterable<\Illuminate\Http\UploadedFile|null>  $berkas
     * @return int jumlah berkas yang tersimpan
     */
    public static function simpanBerkas(Task $task, $berkas, int $olehId): int
    {
        $n = 0;

        foreach ((array) $berkas as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            self::create([
                'task_id' => $task->id,
                'uploaded_by' => $olehId,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $file->store('evidence/' . $task->id, 'public'),
                'mime' => $file->getClientMimeType(),
                'ukuran' => $file->getSize(),
            ]);
            $n++;
        }

        return $n;
    }

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
