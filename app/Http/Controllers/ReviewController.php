<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Display a listing of reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'hotel', 'booking']);

        // Filter by hotel
        if ($request->has('hotel_id')) {
            $query->where('hotel_id', $request->hotel_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by rating
        if ($request->has('rating')) {
            $query->byRating($request->rating);
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->minRating($request->min_rating);
        }

        // Filter by maximum rating
        if ($request->has('max_rating')) {
            $query->maxRating($request->max_rating);
        }

        // Filter reviews with images
        if ($request->has('with_images') && $request->with_images) {
            $query->withImages();
        }

        // Filter recent reviews
        if ($request->has('recent_days')) {
            $query->recent($request->recent_days);
        }

        // Search in comments
        if ($request->has('search')) {
            $query->where('comment', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'message' => 'Reviews retrieved successfully'
        ]);
    }

    /**
     * Store a newly created review.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Check if booking belongs to authenticated user
        $booking = Booking::findOrFail($validated['booking_id']);
        
        if ($booking->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only review your own bookings'
            ], 403);
        }

        // Check if booking is completed
        if ($booking->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'You can only review completed bookings'
            ], 400);
        }

        // Check if review already exists for this booking
        if (Review::where('booking_id', $validated['booking_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Review already exists for this booking'
            ], 400);
        }

        // Handle image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $imagePaths[] = $path;
            }
        }

        // Create review
        $review = Review::create([
            'user_id' => Auth::id(),
            'hotel_id' => $booking->hotel_id,
            'booking_id' => $validated['booking_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'images' => $imagePaths
        ]);

        // Update hotel rating
        $this->updateHotelRating($booking->hotel_id);

        $review->load(['user', 'hotel', 'booking']);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review created successfully'
        ], 201);
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['user', 'hotel', 'booking']);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review retrieved successfully'
        ]);
    }

    /**
     * Update the specified review.
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        // Check if review belongs to authenticated user
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own reviews'
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            // Delete old images
            if ($review->images) {
                foreach ($review->images as $imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
            }

            // Upload new images
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('reviews', 'public');
                $imagePaths[] = $path;
            }
            $validated['images'] = $imagePaths;
        }

        $review->update($validated);

        // Update hotel rating if rating changed
        if (isset($validated['rating'])) {
            $this->updateHotelRating($review->hotel_id);
        }

        $review->load(['user', 'hotel', 'booking']);

        return response()->json([
            'success' => true,
            'data' => $review,
            'message' => 'Review updated successfully'
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review): JsonResponse
    {
        // Check if review belongs to authenticated user or user is admin
        if ($review->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own reviews'
            ], 403);
        }

        // Delete images
        if ($review->images) {
            foreach ($review->images as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $hotelId = $review->hotel_id;
        $review->delete();

        // Update hotel rating
        $this->updateHotelRating($hotelId);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully'
        ]);
    }

    /**
     * Get reviews for a specific hotel.
     */
    public function getHotelReviews(Request $request, $hotelId): JsonResponse
    {
        $hotel = Hotel::findOrFail($hotelId);

        $query = Review::where('hotel_id', $hotelId)
            ->with(['user', 'booking']);

        // Apply filters
        if ($request->has('rating')) {
            $query->byRating($request->rating);
        }

        if ($request->has('min_rating')) {
            $query->minRating($request->min_rating);
        }

        if ($request->has('with_images') && $request->with_images) {
            $query->withImages();
        }

        if ($request->has('recent_days')) {
            $query->recent($request->recent_days);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 10);
        $reviews = $query->paginate($perPage);

        // Get rating statistics
        $ratingStats = Review::where('hotel_id', $hotelId)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $totalReviews = array_sum($ratingStats);
        $averageRating = $totalReviews > 0 ? 
            array_sum(array_map(fn($rating, $count) => $rating * $count, array_keys($ratingStats), $ratingStats)) / $totalReviews : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'hotel' => $hotel,
                'reviews' => $reviews,
                'statistics' => [
                    'total_reviews' => $totalReviews,
                    'average_rating' => round($averageRating, 1),
                    'rating_distribution' => $ratingStats,
                    'positive_reviews' => ($ratingStats[4] ?? 0) + ($ratingStats[5] ?? 0),
                    'negative_reviews' => ($ratingStats[1] ?? 0) + ($ratingStats[2] ?? 0),
                    'neutral_reviews' => $ratingStats[3] ?? 0,
                ]
            ],
            'message' => 'Hotel reviews retrieved successfully'
        ]);
    }

    /**
     * Get reviews by authenticated user.
     */
    public function getUserReviews(Request $request): JsonResponse
    {
        $query = Review::where('user_id', Auth::id())
            ->with(['hotel', 'booking']);

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 10);
        $reviews = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'message' => 'User reviews retrieved successfully'
        ]);
    }

    /**
     * Check if user can review a booking.
     */
    public function canReview($bookingId): JsonResponse
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'can_review' => false,
                'message' => 'Booking not found or does not belong to you'
            ], 404);
        }

        $canReview = $booking->status === 'completed' && 
                    !Review::where('booking_id', $bookingId)->exists();

        return response()->json([
            'success' => true,
            'can_review' => $canReview,
            'booking' => $booking,
            'message' => $canReview ? 'You can review this booking' : 'Cannot review this booking'
        ]);
    }

    /**
     * Get review statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $totalReviews = Review::count();
        $averageRating = Review::avg('rating');
        
        $ratingDistribution = Review::selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $recentReviews = Review::recent(7)->count();
        $reviewsWithImages = Review::withImages()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 1),
                'rating_distribution' => $ratingDistribution,
                'recent_reviews' => $recentReviews,
                'reviews_with_images' => $reviewsWithImages,
                'positive_reviews' => Review::where('rating', '>=', 4)->count(),
                'negative_reviews' => Review::where('rating', '<=', 2)->count(),
                'neutral_reviews' => Review::where('rating', 3)->count(),
            ],
            'message' => 'Review statistics retrieved successfully'
        ]);
    }

    /**
     * Update hotel rating based on reviews.
     */
    private function updateHotelRating($hotelId): void
    {
        $hotel = Hotel::find($hotelId);
        if (!$hotel) return;

        $reviews = Review::where('hotel_id', $hotelId);
        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating');

        $hotel->update([
            'rating' => $totalReviews > 0 ? round($averageRating, 1) : 0,
            'total_reviews' => $totalReviews
        ]);
    }
}