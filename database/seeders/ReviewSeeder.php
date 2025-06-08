<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Booking;

class ReviewSeeder extends Seeder
{
    public function run()
    {
        $bookings = Booking::where('status', 'checked_out')->get();

        foreach ($bookings as $booking) {
            // 70% chance untuk membuat review
            if (rand(1, 10) <= 7) {
                Review::create([
                    'user_id' => $booking->user_id,
                    'hotel_id' => $booking->hotel_id,
                    'booking_id' => $booking->id,
                    'rating' => rand(3, 5),
                    'comment' => $this->getRandomComment(),
                    'images' => rand(1, 10) <= 3 ? [
                        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop'
                    ] : null,
                ]);
            }
        }
    }

    private function getRandomComment()
    {
        $comments = [
            'Hotel yang sangat nyaman dengan pelayanan yang memuaskan.',
            'Lokasi strategis dan kamar bersih. Akan kembali lagi.',
            'Staff ramah dan fasilitas lengkap. Recommended!',
            'Pemandangan bagus dan makanan enak. Overall satisfied.',
            'Kamar luas dan bersih, tapi AC agak berisik.',
            'Pelayanan excellent, breakfast variety juga bagus.',
            'Good value for money, cocok untuk business trip.',
            'Hotelnya bagus tapi parking area agak sempit.',
            'Amazing experience! Staff very helpful.',
            'Clean room, comfortable bed, will stay again.'
        ];
        return $comments[array_rand($comments)];
    }
}
