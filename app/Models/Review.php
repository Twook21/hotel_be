<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'hotel_id',
        'booking_id',
        'rating',
        'comment',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
        'rating' => 'integer',
        'user_id' => 'integer',
        'hotel_id' => 'integer',
        'booking_id' => 'integer',
    ];

    /**
     * Get the user that owns the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the hotel that owns the review.
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the booking that owns the review.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope to get reviews by rating.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to get reviews with minimum rating.
     */
    public function scopeMinRating($query, int $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope to get reviews with maximum rating.
     */
    public function scopeMaxRating($query, int $maxRating)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    /**
     * Scope to get reviews with images.
     */
    public function scopeWithImages($query)
    {
        return $query->whereNotNull('images');
    }

    /**
     * Scope to get reviews without images.
     */
    public function scopeWithoutImages($query)
    {
        return $query->whereNull('images');
    }

    /**
     * Scope to get recent reviews.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get rating stars as string.
     */
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get rating label.
     */
    public function getRatingLabelAttribute(): string
    {
        $labels = [
            1 => 'Sangat Buruk',
            2 => 'Buruk',
            3 => 'Cukup',
            4 => 'Baik',
            5 => 'Sangat Baik',
        ];

        return $labels[$this->rating] ?? 'Unknown';
    }

    /**
     * Check if review has images.
     */
    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    /**
     * Get image count.
     */
    public function getImageCountAttribute(): int
    {
        return $this->images ? count($this->images) : 0;
    }

    /**
     * Get formatted created date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d M Y');
    }

    /**
     * Get review excerpt (first 100 characters).
     */
    public function getExcerptAttribute(): string
    {
        if (!$this->comment) {
            return '';
        }

        return strlen($this->comment) > 100
            ? substr($this->comment, 0, 100) . '...'
            : $this->comment;
    }

    /**
     * Check if review is positive (rating >= 4).
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if review is negative (rating <= 2).
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Check if review is neutral (rating = 3).
     */
    public function isNeutral(): bool
    {
        return $this->rating === 3;
    }
}
