<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of rooms.
     */
    public function index(Request $request)
    {
        $query = Room::with(['roomType.hotel']);
        
        // Filter by hotel
        if ($request->has('hotel_id')) {
            $query->whereHas('roomType', function($q) use ($request) {
                $q->where('hotel_id', $request->hotel_id);
            });
        }
        
        // Filter by room type
        if ($request->has('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }
        
        // Filter by floor
        if ($request->has('floor')) {
            $query->where('floor', $request->floor);
        }
        
        // Search by room number
        if ($request->has('search')) {
            $query->where('room_number', 'like', '%' . $request->search . '%');
        }
        
        // Sort by room number or floor
        $sortBy = $request->get('sort', 'room_number');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        $rooms = $query->paginate(15);
        
        return response()->json($rooms);
    }

    /**
     * Store a newly created room.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:10',
            'floor' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied,maintenance,out_of_order',
            'is_available' => 'boolean',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if room number already exists for the same hotel
        $roomType = RoomType::findOrFail($request->room_type_id);
        $existingRoom = Room::whereHas('roomType', function($q) use ($roomType) {
            $q->where('hotel_id', $roomType->hotel_id);
        })->where('room_number', $request->room_number)->first();

        if ($existingRoom) {
            return response()->json(['error' => 'Room number already exists in this hotel'], 422);
        }

        $room = Room::create($request->all());
        return response()->json($room->load(['roomType.hotel']), 201);
    }

    /**
     * Display the specified room.
     */
    public function show($id)
    {
        $room = Room::with(['roomType.hotel', 'bookings' => function($q) {
            $q->with(['user', 'payment'])->orderBy('check_in_date', 'desc');
        }])->findOrFail($id);
        
        return response()->json($room);
    }

    /**
     * Update the specified room.
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'room_type_id' => 'sometimes|required|exists:room_types,id',
            'room_number' => 'sometimes|required|string|max:10',
            'floor' => 'sometimes|required|integer|min:1',
            'status' => 'sometimes|required|in:available,occupied,maintenance,out_of_order',
            'is_available' => 'boolean',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if room number already exists for the same hotel (excluding current room)
        if ($request->has('room_number') || $request->has('room_type_id')) {
            $roomTypeId = $request->room_type_id ?? $room->room_type_id;
            $roomNumber = $request->room_number ?? $room->room_number;
            
            $roomType = RoomType::findOrFail($roomTypeId);
            $existingRoom = Room::whereHas('roomType', function($q) use ($roomType) {
                $q->where('hotel_id', $roomType->hotel_id);
            })->where('room_number', $roomNumber)
              ->where('id', '!=', $id)
              ->first();

            if ($existingRoom) {
                return response()->json(['error' => 'Room number already exists in this hotel'], 422);
            }
        }

        $room->update($request->all());
        return response()->json($room->load(['roomType.hotel']));
    }

    /**
     * Remove the specified room.
     */
    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        
        // Check if room has active bookings
        $activeBookings = $room->bookings()->whereIn('status', ['pending', 'confirmed'])->count();
        if ($activeBookings > 0) {
            return response()->json(['error' => 'Cannot delete room with active bookings'], 422);
        }
        
        $room->delete();
        return response()->json(['message' => 'Room deleted successfully']);
    }

    /**
     * Get available rooms by room type and date range.
     */
    public function getAvailableRooms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_type_id' => 'required|exists:room_types,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rooms = Room::where('room_type_id', $request->room_type_id)
            ->where('is_available', true)
            ->where('status', 'available')
            ->whereDoesntHave('bookings', function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('check_in_date', '<', $request->check_out_date)
                          ->where('check_out_date', '>', $request->check_in_date)
                          ->whereIn('status', ['pending', 'confirmed']);
                });
            })
            ->with(['roomType.hotel'])
            ->orderBy('room_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rooms,
            'message' => 'Available rooms retrieved successfully'
        ]);
    }

    /**
     * Update room status.
     */
    public function updateStatus(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,occupied,maintenance,out_of_order',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update availability based on status
        $isAvailable = $request->status === 'available';
        
        $room->update([
            'status' => $request->status,
            'is_available' => $isAvailable,
            'notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'data' => $room->load(['roomType.hotel']),
            'message' => 'Room status updated successfully'
        ]);
    }

    /**
     * Get rooms by hotel.
     */
    public function getHotelRooms(Request $request, $hotelId)
    {
        $query = Room::whereHas('roomType', function($q) use ($hotelId) {
            $q->where('hotel_id', $hotelId);
        })->with(['roomType']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Filter by floor
        if ($request->has('floor')) {
            $query->where('floor', $request->floor);
        }

        // Filter by room type
        if ($request->has('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        $rooms = $query->orderBy('floor')->orderBy('room_number')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $rooms,
            'message' => 'Hotel rooms retrieved successfully'
        ]);
    }

    /**
     * Get room statistics.
     */
    public function getStatistics(Request $request)
    {
        $query = Room::query();

        // Filter by hotel if provided
        if ($request->has('hotel_id')) {
            $query->whereHas('roomType', function($q) use ($request) {
                $q->where('hotel_id', $request->hotel_id);
            });
        }

        $totalRooms = $query->count();
        $availableRooms = $query->where('status', 'available')->count();
        $occupiedRooms = $query->where('status', 'occupied')->count();
        $maintenanceRooms = $query->where('status', 'maintenance')->count();
        $outOfOrderRooms = $query->where('status', 'out_of_order')->count();

        $statusDistribution = [
            'available' => $availableRooms,
            'occupied' => $occupiedRooms,
            'maintenance' => $maintenanceRooms,
            'out_of_order' => $outOfOrderRooms
        ];

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_rooms' => $totalRooms,
                'available_rooms' => $availableRooms,
                'occupied_rooms' => $occupiedRooms,
                'maintenance_rooms' => $maintenanceRooms,
                'out_of_order_rooms' => $outOfOrderRooms,
                'status_distribution' => $statusDistribution,
                'occupancy_rate' => $occupancyRate
            ],
            'message' => 'Room statistics retrieved successfully'
        ]);
    }
}