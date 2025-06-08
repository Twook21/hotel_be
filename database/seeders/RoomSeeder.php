<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\RoomType;

class RoomSeeder extends Seeder
{
    public function run()
    {
        $roomTypes = RoomType::all();

        foreach ($roomTypes as $roomType) {
            // Buat 5-8 kamar untuk setiap tipe kamar
            $roomCount = rand(5, 8);

            for ($i = 1; $i <= $roomCount; $i++) {
                $floor = ceil($i / 10); // Lantai berdasarkan nomor kamar
                $roomNumber = $floor . str_pad($i, 2, '0', STR_PAD_LEFT);

                Room::create([
                    'room_type_id' => $roomType->id,
                    'room_number' => $roomNumber,
                    'status' => $this->getRandomStatus(),
                ]);
            }
        }
    }

    private function getRandomStatus()
    {
        $statuses = ['available', 'available', 'available', 'available', 'occupied', 'maintenance'];
        return $statuses[array_rand($statuses)];
    }
}
