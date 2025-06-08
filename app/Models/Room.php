<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'status',
    ];

    protected $casts = [
        'room_type_id' => 'integer',
    ];

    /**
     * Get the room type that owns the room.
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Get the hotel through room type.
     */
    public function hotel()
    {
        return $this->hasOneThrough(Hotel::class, RoomType::class, 'id', 'id', 'room_type_id', 'hotel_id');
    }

    /**
     * Check if room is available for booking.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if room is occupied.
     */
    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    /**
     * Check if room is under maintenance.
     */
    public function isMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    /**
     * Scope to get available rooms only.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to get occupied rooms only.
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    /**
     * Scope to get rooms under maintenance only.
     */
    public function scopeMaintenance($query)
    {
        return $query->where('status', 'maintenance');
    }

    /**
     * Get room status label.
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'available' => 'Tersedia',
            'occupied' => 'Ditempati',
            'maintenance' => 'Maintenance',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    /**
     * Get room full name (Hotel + Room Type + Room Number).
     */
    public function getFullNameAttribute(): string
    {
        return $this->roomType->hotel->name . ' - ' . $this->roomType->name . ' (' . $this->room_number . ')';
    }
}
