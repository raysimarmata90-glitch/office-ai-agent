<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionTemplate extends Model
{
    protected $fillable = [
        'department_id',
        'step_number',
        'question_text',
        'system_prompt',
        'expected_format',
        'is_required',
        'order',
    ];

    protected $casts = [
        'expected_format' => 'array',
        'is_required' => 'boolean',
    ];

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
