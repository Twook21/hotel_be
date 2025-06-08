<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;

class HotelSeeder extends Seeder
{
    public function run()
    {
        $hotels = [
            [
                'name' => 'Grand Hotel Jakarta',
                'description' => 'Hotel mewah di pusat kota Jakarta dengan fasilitas lengkap dan pelayanan terbaik. Terletak strategis dekat dengan pusat bisnis dan shopping mall.',
                'address' => 'Jl. MH Thamrin No. 1',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
                'postal_code' => '10310',
                'latitude' => -6.1944,
                'longitude' => 106.8229,
                'phone' => '021-12345678',
                'email' => 'info@grandhoteljakarta.com',
                'facilities' => ['wifi', 'parking', 'pool', 'gym', 'spa', 'restaurant', 'bar', 'conference_room', 'laundry', '24h_front_desk'],
                'images' => [
                    'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&h=600&fit=crop'
                ],
                'rating' => 4.5,
                'total_reviews' => 1250,
                'is_active' => true,
            ],
            [
                'name' => 'Bali Paradise Resort',
                'description' => 'Resort eksotis di Bali dengan pemandangan pantai yang menakjubkan. Cocok untuk liburan romantis dan keluarga dengan berbagai aktivitas menarik.',
                'address' => 'Jl. Pantai Kuta No. 88',
                'city' => 'Badung',
                'province' => 'Bali',
                'postal_code' => '80361',
                'latitude' => -8.7205,
                'longitude' => 115.1693,
                'phone' => '0361-987654',
                'email' => 'reservation@baliparadise.com',
                'facilities' => ['wifi', 'parking', 'pool', 'beach_access', 'spa', 'restaurant', 'bar', 'water_sports', 'kids_club', 'shuttle'],
                'images' => [
                    'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&h=600&fit=crop'
                ],
                'rating' => 4.8,
                'total_reviews' => 890,
                'is_active' => true,
            ],
            [
                'name' => 'Yogya Heritage Hotel',
                'description' => 'Hotel bersejarah di jantung Yogyakarta dengan arsitektur tradisional Jawa. Dekat dengan Malioboro Street dan Keraton Yogyakarta.',
                'address' => 'Jl. Malioboro No. 52',
                'city' => 'Yogyakarta',
                'province' => 'DI Yogyakarta',
                'postal_code' => '55213',
                'latitude' => -7.7956,
                'longitude' => 110.3695,
                'phone' => '0274-456789',
                'email' => 'info@yogyaheritage.com',
                'facilities' => ['wifi', 'parking', 'restaurant', 'coffee_shop', 'meeting_room', 'laundry', 'tour_desk', 'cultural_center'],
                'images' => [
                    'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=800&h=600&fit=crop'
                ],
                'rating' => 4.2,
                'total_reviews' => 567,
                'is_active' => true,
            ],
            [
                'name' => 'Bandung Mountain Lodge',
                'description' => 'Lodge nyaman di daerah pegunungan Bandung dengan udara sejuk dan pemandangan alam yang indah. Ideal untuk retreat dan healing.',
                'address' => 'Jl. Raya Lembang No. 234',
                'city' => 'Bandung',
                'province' => 'Jawa Barat',
                'postal_code' => '40391',
                'latitude' => -6.8168,
                'longitude' => 107.6137,
                'phone' => '022-789012',
                'email' => 'contact@bandungmountain.com',
                'facilities' => ['wifi', 'parking', 'restaurant', 'garden', 'hiking_trail', 'bonfire_area', 'meeting_room', 'outdoor_activities'],
                'images' => [
                    'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1586375300773-8384e3e4916f?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&h=600&fit=crop'
                ],
                'rating' => 4.3,
                'total_reviews' => 342,
                'is_active' => true,
            ],
            [
                'name' => 'Surabaya Business Hotel',
                'description' => 'Hotel bisnis modern di Surabaya dengan fasilitas lengkap untuk perjalanan dinas. Lokasi strategis dekat dengan pusat bisnis dan bandara.',
                'address' => 'Jl. HR Muhammad No. 100',
                'city' => 'Surabaya',
                'province' => 'Jawa Timur',
                'postal_code' => '60285',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'phone' => '031-345678',
                'email' => 'info@surabayabusiness.com',
                'facilities' => ['wifi', 'parking', 'gym', 'pool', 'restaurant', 'bar', 'conference_room', 'business_center', 'airport_shuttle', '24h_front_desk'],
                'images' => [
                    'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&h=600&fit=crop'
                ],
                'rating' => 4.1,
                'total_reviews' => 678,
                'is_active' => true,
            ]
        ];

        foreach ($hotels as $hotel) {
            Hotel::create($hotel);
        }
    }
}
