<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = [
        'name', 'description', 'address', 'city', 'province',
        'postal_code', 'latitude', 'longitude', 'phone', 'email',
        'facilities', 'images', 'rating', 'total_reviews', 'is_active'
    ];

    protected $casts = [
        'facilities' => 'array',
        'images' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
