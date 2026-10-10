<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'topic',
        'difficulty',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'explanation',
        'source_reference',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope to active questions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Quiz that owns this question.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Map of option key => text.
     */
    public function getOptionsAttribute(): array
    {
        return [
            'a' => $this->option_a,
            'b' => $this->option_b,
            'c' => $this->option_c,
            'd' => $this->option_d,
        ];
    }

    /**
     * The full text of the correct answer.
     */
    public function getCorrectOptionTextAttribute(): string
    {
        $key = 'option_' . strtolower($this->correct_option);
        return $this->{$key} ?? '';
    }

    /**
     * Check if a given option is correct.
     */
    public function isOptionCorrect(?string $selectedOption): bool
    {
        if (empty($selectedOption)) {
            return false;
        }

        return strtolower(trim($selectedOption)) === strtolower(trim($this->correct_option));
    }

    /**
     * Client-safe representation for interactive gameplay.
     * EXCLUDES correct_option and explanation to prevent inspect-element cheating!
     */
    public function toClientPayload(): array
    {
        return [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'options' => [
                'a' => $this->option_a,
                'b' => $this->option_b,
                'c' => $this->option_c,
                'd' => $this->option_d,
            ],
            'display_order' => $this->display_order,
        ];
    }
}
