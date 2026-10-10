<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuizAttempt extends Model
{
    use HasFactory;

    public const TIME_LIMIT_SECONDS = 120;
    public const GRACE_PERIOD_SECONDS = 10; // Server grace period for network latency

    protected $fillable = [
        'quiz_id',
        'attempt_token',
        'score',
        'time_taken',
        'attempted_at',
        'started_at',
        'completed_at',
        'status',
        'answers',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'time_taken' => 'integer',
            'attempted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'answers' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attempt) {
            if (empty($attempt->attempt_token)) {
                $attempt->attempt_token = (string) Str::uuid();
            }
            if (empty($attempt->started_at)) {
                $attempt->started_at = now();
            }
            if (empty($attempt->attempted_at)) {
                $attempt->attempted_at = now();
            }
        });
    }

    /**
     * Use attempt_token for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'attempt_token';
    }

    /**
     * Quiz that this attempt belongs to.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Check if attempt is currently active and within time limit.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if attempt was completed and scored.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if attempt has expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Get remaining seconds from the 120s countdown.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (!$this->isInProgress()) {
            return 0;
        }

        $elapsed = max(0, now()->timestamp - $this->started_at->timestamp);
        return max(0, self::TIME_LIMIT_SECONDS - $elapsed);
    }

    /**
     * Check if total elapsed time exceeds allowed limit + grace period.
     */
    public function hasExceededTimeLimit(): bool
    {
        $elapsed = max(0, now()->timestamp - $this->started_at->timestamp);
        return $elapsed > (self::TIME_LIMIT_SECONDS + self::GRACE_PERIOD_SECONDS);
    }

    /**
     * Calculate score percentage.
     */
    public function getPercentageAttribute(): float
    {
        return round(($this->score / 10) * 100, 1);
    }

    /**
     * Formatted duration string (e.g., "1m 14s").
     */
    public function getFormattedTimeTakenAttribute(): string
    {
        $seconds = $this->time_taken ?? 0;
        $minutes = floor($seconds / 60);
        $remSec = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$remSec}s";
        }
        return "{$remSec}s";
    }
}
