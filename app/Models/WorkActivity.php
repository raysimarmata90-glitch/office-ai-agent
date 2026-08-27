<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkActivity Model
 * 
 * Menyimpan aktivitas kerja terstruktur dari hasil percakapan onboarding.
 * Best practice: pisahkan data terstruktur dari messages (yang semi-structured).
 */
class WorkActivity extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'project_name',
        'objective',
        'objective_parsed',
        'expectation',
        'expectation_from_options',
        'deliverable',
        'deliverable_from_options',
        'deliverable_parsed',
        'current_task',
        'task_from_planning',
        'progress_detail',
        'progress_percentage',
        'is_complete',
        'estimation_text',
        'estimation_duration',
        'priority_level',
        'raw_answers',
        'completed_at',
    ];

    protected $casts = [
        'objective_parsed' => 'array',
        'expectation_from_options' => 'boolean',
        'deliverable_from_options' => 'boolean',
        'deliverable_parsed' => 'array',
        'task_from_planning' => 'boolean',
        'progress_percentage' => 'integer',
        'is_complete' => 'boolean',
        'estimation_duration' => 'array',
        'raw_answers' => 'array',
        'completed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get summary of work activity
     */
    public function getSummary(): string
    {
        $parts = [];
        
        if ($this->project_name) {
            $parts[] = "Proyek: {$this->project_name}";
        }
        
        if ($this->objective) {
            $parts[] = "Objektif: {$this->objective}";
        }
        
        if ($this->deliverable) {
            $parts[] = "Deliverable: {$this->deliverable}";
        }
        
        if ($this->current_task) {
            $parts[] = "Task: {$this->current_task}";
        }
        
        if ($this->progress_detail) {
            $parts[] = "Progress: {$this->progress_detail}";
        }
        
        if ($this->estimation_text) {
            $parts[] = "Estimasi: {$this->estimation_text}";
        }
        
        return implode(' | ', $parts);
    }
    
    /**
     * Check if activity is complete (has all required fields)
     */
    public function isComplete(): bool
    {
        return !empty($this->objective) 
            && !empty($this->expectation) 
            && !empty($this->deliverable)
            && !empty($this->current_task);
    }
    
    /**
     * Get estimation in hours (normalized)
     */
    public function getEstimationInHours(): ?float
    {
        if (empty($this->estimation_duration)) {
            return null;
        }
        
        $value = $this->estimation_duration['value'] ?? 0;
        $unit = $this->estimation_duration['unit'] ?? 'hours';
        
        return match ($unit) {
            'hours' => $value,
            'days' => $value * 8, // 8 jam per hari
            'weeks' => $value * 40, // 40 jam per minggu
            'months' => $value * 160, // ~160 jam per bulan
            default => $value,
        };
    }
}
