<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Models\Hotel;
use App\Models\RoomType;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run()
    {
        $users = User::where('role', 'user')->get();
        $hotels = Hotel::all();

        // Buat 20 booking dummy
        for ($i = 0; $i < 20; $i++) {
            $user = $users->random();
            $hotel = $hotels->random();
            $roomType = $hotel->roomTypes->random();

            $checkInDate = Carbon::now()->addDays(rand(-30, 60));
            $checkOutDate = $checkInDate->copy()->addDays(rand(1, 7));
            $nights = $checkInDate->diffInDays($checkOutDate);

            $guests = rand(1, $roomType->capacity);
            $roomPrice = $roomType->price_per_night;
            $totalPrice = $roomPrice * $nights;

            Booking::create([
                'booking_code' => 'BK' . strtoupper(uniqid()),
                'user_id' => $user->id,
                'hotel_id' => $hotel->id,
                'room_type_id' => $roomType->id,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'nights' => $nights,
                'guests' => $guests,
                'room_price' => $roomPrice,
                'total_price' => $totalPrice,
                'status' => $this->getRandomBookingStatus(),
                'special_requests' => $this->getRandomSpecialRequest(),
                'confirmed_at' => $checkInDate->isPast() ? $checkInDate->subDays(1) : null,
            ]);
        }
    }

    private function getRandomBookingStatus()
    {
        $statuses = ['confirmed', 'confirmed', 'confirmed', 'pending', 'checked_out', 'cancelled'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomSpecialRequest()
    {
        $requests = [
            null,
            null,
            'Late check-in',
            'High floor room',
            'Twin bed arrangement',
            'Extra pillow',
            'Airport pickup required',
            'Vegetarian breakfast',
            'Quiet room please',
            'Room with city view'
        ];
        return $requests[array_rand($requests)];
    }
}
