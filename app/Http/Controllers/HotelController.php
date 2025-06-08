<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $query = Hotel::query();
        
        // Filter by city
        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->where('rating', '>=', $request->min_rating);
        }
        
        // Sort by rating or name
        $sortBy = $request->get('sort', 'name');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        $hotels = $query->with(['roomTypes'])->paginate(15);
        
        return response()->json($hotels);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'facilities' => 'nullable|array',
            'images' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hotel = Hotel::create($request->all());
        return response()->json($hotel, 201);
    }

    public function show($id)
    {
        $hotel = Hotel::with(['roomTypes', 'reviews.user', 'bookings'])->findOrFail($id);
        return response()->json($hotel);
    }

    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'address' => 'sometimes|required|string|max:500',
            'city' => 'sometimes|required|string|max:100',
            'province' => 'sometimes|required|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'facilities' => 'nullable|array',
            'images' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hotel->update($request->all());
        return response()->json($hotel);
    }

    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->delete();
        return response()->json(['message' => 'Hotel deleted successfully']);
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'nullable|string',
            'check_in' => 'nullable|date|after_or_equal:today',
            'check_out' => 'nullable|date|after:check_in',
            'guests' => 'nullable|integer|min:1',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'facilities' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Hotel::where('is_active', true);

        if ($request->city) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->facilities && is_array($request->facilities)) {
            foreach ($request->facilities as $facility) {
                $query->whereJsonContains('facilities', $facility);
            }
        }

        $hotels = $query->with(['roomTypes' => function($q) use ($request) {
            $q->where('is_available', true);
            if ($request->min_price) {
                $q->where('price_per_night', '>=', $request->min_price);
            }
            if ($request->max_price) {
                $q->where('price_per_night', '<=', $request->max_price);
            }
        }])->paginate(15);

        return response()->json($hotels);
    }
}