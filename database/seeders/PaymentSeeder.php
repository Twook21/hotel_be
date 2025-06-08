<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Booking;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        $bookings = Booking::whereIn('status', ['confirmed', 'checked_out'])->get();

        foreach ($bookings as $booking) {
            Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => $this->getRandomPaymentMethod(),
                'amount' => $booking->total_price,
                'status' => 'paid',
                'transaction_id' => 'TXN' . strtoupper(uniqid()),
                'payment_details' => [
                    'gateway' => 'midtrans',
                    'payment_type' => 'credit_card',
                    'bank' => 'BCA',
                ],
                'paid_at' => $booking->confirmed_at,
            ]);
        }
    }

    private function getRandomPaymentMethod()
    {
        $methods = ['credit_card', 'bank_transfer', 'e_wallet', 'virtual_account'];
        return $methods[array_rand($methods)];
    }
}
