<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomType;
use App\Models\Hotel;

class RoomTypeSeeder extends Seeder
{
    public function run()
    {
        $hotels = Hotel::all();

        foreach ($hotels as $hotel) {
            // Standard Room
            RoomType::create([
                'hotel_id' => $hotel->id,
                'name' => 'Standard Room',
                'description' => 'Kamar standar dengan fasilitas dasar yang nyaman. Cocok untuk perjalanan bisnis atau liburan singkat.',
                'price_per_night' => $this->getBasePrice($hotel->city) * 1,
                'capacity' => 2,
                'size' => 25.0,
                'facilities' => ['ac', 'tv', 'wifi', 'private_bathroom', 'desk', 'wardrobe'],
                'images' => [
                    'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=600&fit=crop'
                ],
                'is_available' => true,
            ]);

            // Deluxe Room
            RoomType::create([
                'hotel_id' => $hotel->id,
                'name' => 'Deluxe Room',
                'description' => 'Kamar deluxe dengan fasilitas lebih lengkap dan pemandangan yang menarik. Memberikan kenyamanan ekstra untuk tamu.',
                'price_per_night' => $this->getBasePrice($hotel->city) * 1.5,
                'capacity' => 2,
                'size' => 35.0,
                'facilities' => ['ac', 'tv', 'wifi', 'private_bathroom', 'minibar', 'safe', 'bathtub', 'balcony', 'desk', 'wardrobe'],
                'images' => [
                    'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&h=600&fit=crop'
                ],
                'is_available' => true,
            ]);

            // Suite Room
            RoomType::create([
                'hotel_id' => $hotel->id,
                'name' => 'Suite Room',
                'description' => 'Kamar suite mewah dengan ruang tamu terpisah. Ideal untuk tamu VIP atau acara khusus dengan fasilitas premium.',
                'price_per_night' => $this->getBasePrice($hotel->city) * 2.5,
                'capacity' => 4,
                'size' => 60.0,
                'facilities' => ['ac', 'tv', 'wifi', 'private_bathroom', 'minibar', 'safe', 'jacuzzi', 'living_room', 'kitchenette', 'balcony', 'work_area', 'wardrobe'],
                'images' => [
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&h=600&fit=crop'
                ],
                'is_available' => true,
            ]);

            // Family Room (hanya untuk hotel tertentu)
            if (in_array($hotel->city, ['Bandung', 'Bali', 'Yogyakarta'])) {
                RoomType::create([
                    'hotel_id' => $hotel->id,
                    'name' => 'Family Room',
                    'description' => 'Kamar keluarga yang luas dengan tempat tidur tambahan. Sempurna untuk liburan keluarga dengan anak-anak.',
                    'price_per_night' => $this->getBasePrice($hotel->city) * 2,
                    'capacity' => 6,
                    'size' => 45.0,
                    'facilities' => ['ac', 'tv', 'wifi', 'private_bathroom', 'minibar', 'kids_amenities', 'extra_bed', 'balcony', 'desk', 'wardrobe'],
                    'images' => [
                        'https://images.unsplash.com/photo-1586375300773-8384e3e4916f?w=800&h=600&fit=crop',
                        'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&h=600&fit=crop'
                    ],
                    'is_available' => true,
                ]);
            }
        }
    }

    private function getBasePrice($city)
    {
        $basePrices = [
            'Jakarta' => 800000,
            'Badung' => 1200000, // Bali
            'Yogyakarta' => 400000,
            'Bandung' => 500000,
            'Surabaya' => 600000,
        ];

        return $basePrices[$city] ?? 500000;
    }
}
