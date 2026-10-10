<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    public const REQUIRED_QUESTION_COUNT = 10;
    public const TIME_LIMIT_SECONDS = 120;
    public const MAX_SCORE = 10;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Questions belonging to this quiz in display order.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * Quiz attempts recorded for this quiz.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Scope to only include published quizzes with valid publication date.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to strictly include quizzes that are published AND have exactly 10 questions.
     */
    public function scopePlayable(Builder $query): Builder
    {
        return $query->published()->has('questions', '=', self::REQUIRED_QUESTION_COUNT);
    }

    /**
     * Check if this quiz satisfies all criteria to be published.
     * Must have exactly 10 valid questions, each with 4 non-empty options and a valid correct option.
     */
    public function validateForPublication(&$errors = []): bool
    {
        $errors = [];
        $questions = $this->questions()->get();

        if ($questions->count() !== self::REQUIRED_QUESTION_COUNT) {
            $errors[] = "A quiz must have exactly " . self::REQUIRED_QUESTION_COUNT . " questions to be published. Current question count: {$questions->count()}.";
        }

        foreach ($questions as $index => $q) {
            $num = $index + 1;
            if (empty(trim($q->question_text))) {
                $errors[] = "Question #{$num} is missing question text.";
            }
            if (empty(trim($q->option_a)) || empty(trim($q->option_b)) || empty(trim($q->option_c)) || empty(trim($q->option_d))) {
                $errors[] = "Question #{$num} must have all four multiple-choice options filled.";
            }
            if (!in_array(strtolower($q->correct_option), ['a', 'b', 'c', 'd'])) {
                $errors[] = "Question #{$num} must designate a valid correct answer ('a', 'b', 'c', or 'd').";
            }
        }

        return empty($errors);
    }

    /**
     * Fast boolean check if quiz can be published.
     */
    public function canBePublished(): bool
    {
        return $this->validateForPublication();
    }

    /**
     * Check if quiz is currently published and playable.
     */
    public function isPlayable(): bool
    {
        return $this->status === 'published' 
            && $this->published_at !== null 
            && $this->published_at->lte(now()) 
            && $this->questions()->count() === self::REQUIRED_QUESTION_COUNT;
    }
}
