<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatSession extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'project_deliverable_id',
        'current_step',
        'collected_data',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'collected_data' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectDeliverable(): BelongsTo
    {
        return $this->belongsTo(ProjectDeliverable::class);
    }

    /**
     * Get or create active session for user
     */
    public static function getOrCreateSession(int $userId): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'is_active' => true,
            ],
            [
                'current_step' => 'select_client',
                'collected_data' => [],
            ]
        );
    }

    /**
     * Move to next step
     */
    public function moveToNextStep(): void
    {
        $steps = [
            'select_client' => 'select_deliverable',
            'select_deliverable' => 'objective_as_is',
            'objective_as_is' => 'timeline_validation',
            'timeline_validation' => 'task_inquiry',
            'task_inquiry' => 'percentage_allocation',
            'percentage_allocation' => 'completed',
        ];

        if (isset($steps[$this->current_step])) {
            $this->current_step = $steps[$this->current_step];
            $this->save();
        }
    }

    /**
     * Save data for current step
     */
    public function saveStepData(string $key, $value): void
    {
        $data = $this->collected_data ?? [];
        $data[$key] = $value;
        $this->collected_data = $data;
        $this->save();
    }

    /**
     * Get data for specific key
     */
    public function getStepData(string $key, $default = null)
    {
        return $this->collected_data[$key] ?? $default;
    }

    /**
     * Reset session
     */
    public function reset(): void
    {
        $this->update([
            'project_id' => null,
            'project_deliverable_id' => null,
            'current_step' => 'select_client',
            'collected_data' => [],
        ]);
    }
}
