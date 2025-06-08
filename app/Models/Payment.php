<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_method',
        'amount',
        'status',
        'transaction_id',
        'payment_details',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'booking_id' => 'integer',
    ];

    /**
     * Get the booking that owns the payment.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user through booking.
     */
    public function user()
    {
        return $this->hasOneThrough(User::class, Booking::class, 'id', 'id', 'booking_id', 'user_id');
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if payment is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Scope to get paid payments only.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to get pending payments only.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed payments only.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get refunded payments only.
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Get payment status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'refunded' => 'Dikembalikan',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    /**
     * Get payment method label.
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        $labels = [
            'credit_card' => 'Kartu Kredit',
            'bank_transfer' => 'Transfer Bank',
            'e_wallet' => 'E-Wallet',
            'virtual_account' => 'Virtual Account',
            'cash' => 'Tunai',
        ];

        return $labels[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(string $transactionId = null): bool
    {
        $this->status = 'paid';
        $this->paid_at = now();

        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }

        return $this->save();
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(): bool
    {
        $this->status = 'failed';
        return $this->save();
    }

    /**
     * Mark payment as refunded.
     */
    public function markAsRefunded(): bool
    {
        $this->status = 'refunded';
        return $this->save();
    }

    /**
     * Check if payment is overdue (pending for more than 24 hours).
     */
    public function isOverdue(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->created_at->addHours(24)->isPast();
    }
}

