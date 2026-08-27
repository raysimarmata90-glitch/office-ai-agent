<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ProjectDeliverable extends Model
{
    protected $fillable = [
        'project_id',
        'code',
        'deliverable_name',
        'category',
        'pic',
        'progress_update',
        'next_steps',
        'due_date',
        'completion_percentage',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'due_date' => 'date',
            'completion_percentage' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function chatSessions(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    /**
     * Check if deliverable is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) return false;
        return Carbon::parse($this->due_date)->isPast() && !$this->is_completed;
    }

    /**
     * Get category badge color
     */
    public function getCategoryColor(): string
    {
        return match($this->category) {
            'COMMERCIAL' => 'blue',
            'TECH' => 'green',
            'EKSPANSI' => 'purple',
            'LEGAL' => 'red',
            default => 'gray',
        };
    }
}
