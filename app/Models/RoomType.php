<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'hotel_id', 'name', 'description', 'price_per_night',
        'capacity', 'size', 'facilities', 'images', 'is_available'
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'size' => 'decimal:2',
        'facilities' => 'array',
        'images' => 'array',
        'is_available' => 'boolean',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
