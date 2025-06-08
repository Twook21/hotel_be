<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = RoomType::with('hotel');
        
        // Filter by hotel
        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }
        
        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }
        
        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price_per_night', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }
        
        $roomTypes = $query->paginate(15);
        
        return response()->json($roomTypes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price_per_night' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'size' => 'nullable|numeric|min:0',
            'facilities' => 'nullable|array',
            'images' => 'nullable|array',
            'is_available' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $roomType = RoomType::create($request->all());
        return response()->json($roomType->load('hotel'), 201);
    }

    public function show($id)
    {
        $roomType = RoomType::with(['hotel', 'rooms', 'bookings'])->findOrFail($id);
        return response()->json($roomType);
    }

    public function update(Request $request, $id)
    {
        $roomType = RoomType::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'hotel_id' => 'sometimes|required|exists:hotels,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price_per_night' => 'sometimes|required|numeric|min:0',
            'capacity' => 'sometimes|required|integer|min:1',
            'size' => 'nullable|numeric|min:0',
            'facilities' => 'nullable|array',
            'images' => 'nullable|array',
            'is_available' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $roomType->update($request->all());
        return response()->json($roomType->load('hotel'));
    }

    public function destroy($id)
    {
        $roomType = RoomType::findOrFail($id);
        $roomType->delete();
        return response()->json(['message' => 'Room type deleted successfully']);
    }
}