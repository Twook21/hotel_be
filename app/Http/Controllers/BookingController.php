<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'hotel', 'roomType', 'payment']);

        // Jika bukan admin, user hanya bisa melihat booking mereka sendiri
        if (!$this->isAdmin($request->user())) {
            $query->where('user_id', $request->user()->id);
        } else {
            // Admin bisa filter by user_id jika diperlukan
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        }

        // Filter by hotel (admin only, atau hotel manager untuk hotel mereka)
        if ($request->has('hotel_id')) {
            if ($this->isAdmin($request->user()) || $this->canAccessHotel($request->user(), $request->hotel_id)) {
                $query->where('hotel_id', $request->hotel_id);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('check_in_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('check_out_date', '<=', $request->end_date);
        }

        // Search by booking code
        if ($request->has('search')) {
            $query->where('booking_code', 'like', '%' . $request->search . '%');
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'room_type_id' => 'required|exists:room_types,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'guests' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check room type capacity
        $roomType = RoomType::findOrFail($request->room_type_id);
        if ($request->guests > $roomType->capacity) {
            return response()->json(['error' => 'Number of guests exceeds room capacity'], 422);
        }

        // Calculate nights and total price
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $nights = $checkIn->diffInDays($checkOut);
        $totalPrice = $roomType->price_per_night * $nights;

        // Generate booking code
        $bookingCode = 'BK' . date('Ymd') . strtoupper(Str::random(6));

        $booking = Booking::create([
            'booking_code' => $bookingCode,
            'user_id' => $request->user()->id, // Selalu gunakan user yang sedang login
            'hotel_id' => $request->hotel_id,
            'room_type_id' => $request->room_type_id,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'nights' => $nights,
            'guests' => $request->guests,
            'room_price' => $roomType->price_per_night,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'special_requests' => $request->special_requests
        ]);

        return response()->json($booking->load(['user', 'hotel', 'roomType']), 201);
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'hotel', 'roomType', 'payment', 'review'])->findOrFail($id);

        // Authorization check
        if (!$this->canAccessBooking($booking, auth()->user())) {
            return response()->json(['error' => 'Unauthorized access to this booking'], 403);
        }

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        // Authorization check
        if (!$this->canAccessBooking($booking, $request->user())) {
            return response()->json(['error' => 'Unauthorized access to this booking'], 403);
        }

        $validator = Validator::make($request->all(), [
            'check_in_date' => 'sometimes|required|date|after_or_equal:today',
            'check_out_date' => 'sometimes|required|date|after:check_in_date',
            'guests' => 'sometimes|required|integer|min:1',
            'special_requests' => 'nullable|string|max:1000',
            'status' => 'sometimes|required|in:pending,confirmed,cancelled,completed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Only allow status updates for admin/hotel manager
        if ($request->has('status') && !$this->isAdmin($request->user()) && !$this->isHotelManager($request->user())) {
            return response()->json(['error' => 'Only admin or hotel manager can update booking status'], 403);
        }

        // Only allow updates if booking is still pending (for regular users)
        if ($booking->status !== 'pending' && !$this->isAdmin($request->user()) && !$this->isHotelManager($request->user())) {
            return response()->json(['error' => 'Cannot update confirmed booking'], 422);
        }

        // Check room capacity if guests changed
        if ($request->has('guests')) {
            $roomType = $booking->roomType;
            if ($request->guests > $roomType->capacity) {
                return response()->json(['error' => 'Number of guests exceeds room capacity'], 422);
            }
        }

        // Recalculate if dates changed
        if ($request->has('check_in_date') || $request->has('check_out_date')) {
            $checkIn = Carbon::parse($request->check_in_date ?? $booking->check_in_date);
            $checkOut = Carbon::parse($request->check_out_date ?? $booking->check_out_date);
            $nights = $checkIn->diffInDays($checkOut);
            $totalPrice = $booking->room_price * $nights;

            $request->merge([
                'nights' => $nights,
                'total_price' => $totalPrice
            ]);
        }

        $booking->update($request->all());
        return response()->json($booking->load(['user', 'hotel', 'roomType']));
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);

        // Authorization check
        if (!$this->canAccessBooking($booking, auth()->user())) {
            return response()->json(['error' => 'Unauthorized access to this booking'], 403);
        }

        // Only allow deletion if booking is pending
        if ($booking->status !== 'pending') {
            return response()->json(['error' => 'Cannot delete non-pending booking'], 422);
        }

        $booking->delete();
        return response()->json(['message' => 'Booking deleted successfully']);
    }

    public function confirm($id)
    {
        $booking = Booking::findOrFail($id);

        // Only admin or hotel manager can confirm bookings
        if (!$this->isAdmin(auth()->user()) && !$this->canManageHotelBooking($booking, auth()->user())) {
            return response()->json(['error' => 'Unauthorized to confirm this booking'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['error' => 'Booking is not pending'], 422);
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);

        return response()->json($booking);
    }

    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);

        // Authorization check - user can cancel their own booking, admin/hotel manager can cancel any
        if (!$this->canAccessBooking($booking, auth()->user()) &&
            !$this->canManageHotelBooking($booking, auth()->user())) {
            return response()->json(['error' => 'Unauthorized to cancel this booking'], 403);
        }

        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return response()->json(['error' => 'Cannot cancel this booking'], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return response()->json($booking);
    }

    /**
     * Get user's own bookings - dedicated endpoint for users
     */
    public function myBookings(Request $request)
    {
        $query = Booking::with(['hotel', 'roomType', 'payment'])
                       ->where('user_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('check_in_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('check_out_date', '<=', $request->end_date);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($bookings);
    }

    /**
     * Get all bookings - admin only
     */
    public function allBookings(Request $request)
    {
        if (!$this->isAdmin($request->user())) {
            return response()->json(['error' => 'Admin access required'], 403);
        }

        return $this->index($request);
    }

    // Helper methods for authorization
    private function canAccessBooking($booking, $user)
    {
        return $booking->user_id === $user->id ||
               $this->isAdmin($user) ||
               $this->canManageHotelBooking($booking, $user);
    }

    private function canManageHotelBooking($booking, $user)
    {
        return $this->isHotelManager($user) &&
               $this->canAccessHotel($user, $booking->hotel_id);
    }

    private function isAdmin($user)
    {
        return $user->hasRole('admin') || $user->role === 'admin';
    }

    private function isHotelManager($user)
    {
        return $user->hasRole('hotel_manager') || $user->role === 'hotel_manager';
    }

    private function canAccessHotel($user, $hotelId)
    {
        // Implementasi ini tergantung pada struktur relasi user-hotel
        // Contoh jika ada relasi many-to-many antara user dan hotel
        return $user->hotels()->where('hotel_id', $hotelId)->exists();

        // Atau jika menggunakan field hotel_id di user table:
        // return $user->hotel_id === $hotelId;
    }
}
