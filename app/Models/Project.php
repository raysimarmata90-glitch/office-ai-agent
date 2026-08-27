<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Project extends Model
{
    protected $fillable = [
        'no',
        'client_or_rd',
        'kd_id',
        'key_deliverables',
        'status',
        'pic',
        'progress_update',
        'next_steps',
        'due_date',
        'is_archived',
        'is_blocked',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'is_blocked' => 'boolean',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Auto-block project jika melewati due date
        static::saving(function (self $project) {
            if ($project->due_date && Carbon::parse($project->due_date)->isPast()) {
                $project->is_blocked = true;
            }
        });
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ProjectDeliverable::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * Get deliverables by category
     */
    public function deliverablesByCategory(string $category): HasMany
    {
        return $this->deliverables()->where('category', $category);
    }

    /**
     * Get completion percentage
     */
    public function completionPercentage(): int
    {
        $total = $this->deliverables->count();
        if ($total === 0) return 0;

        $completed = $this->deliverables->where('is_completed', true)->count();
        return (int) round($completed / $total * 100);
    }

    /**
     * Check if project is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) return false;
        return Carbon::parse($this->due_date)->isPast() && $this->status !== 'Closed';
    }
}

