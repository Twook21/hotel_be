<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\UserController;



// Public routes (no authentication required)
Route::prefix('v1')->group(function () {

    // Hotel Routes - Public
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/{id}', [HotelController::class, 'show']);
    Route::post('/hotels/search', [HotelController::class, 'search']);

    // Room Type Routes - Public (for browsing)
    Route::get('/room-types', [RoomTypeController::class, 'index']);
    Route::get('/room-types/{id}', [RoomTypeController::class, 'show']);

    // Room Routes - Public (for checking availability)
    Route::get('/rooms/available', [RoomController::class, 'getAvailableRooms']);

    // Review Routes - Public (for reading reviews)
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);
    Route::get('/hotels/{hotelId}/reviews', [ReviewController::class, 'getHotelReviews']);
    Route::get('/reviews/statistics', [ReviewController::class, 'getStatistics']);

    // User Registration (public)
    Route::post('/users/register', [UserController::class, 'store']);
    Route::post('/users/login', [UserController::class, 'login']);    // ← PERLU DITAMBAHKAN
    Route::post('/users/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
});

// Protected routes (authentication required)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Booking Routes
    Route::apiResource('bookings', BookingController::class);
    Route::post('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    // Payment Routes
    Route::apiResource('payments', PaymentController::class);
    Route::post('/payments/{id}/mark-as-paid', [PaymentController::class, 'markAsPaid']);
    Route::post('/payments/{id}/mark-as-failed', [PaymentController::class, 'markAsFailed']);
    Route::post('/payments/{id}/mark-as-refunded', [PaymentController::class, 'markAsRefunded']);

    // Review Routes - Protected (for creating/updating/deleting reviews)
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    Route::get('/reviews/user', [ReviewController::class, 'getUserReviews']);
    Route::get('/bookings/{bookingId}/can-review', [ReviewController::class, 'canReview']);

    // User Routes - Self management
    Route::get('/users/profile', function (Request $request) {
        return response()->json($request->user()->load(['bookings', 'reviews']));
    });
    Route::put('/users/profile', function (Request $request) {
        // This would typically be handled by a separate ProfileController
        // but for now we'll reference the UserController
        return app(UserController::class)->update($request, $request->user()->id);
    });
});

// Admin only routes
Route::prefix('v1')->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // Hotel Management - Admin only
    Route::post('/hotels', [HotelController::class, 'store']);
    Route::put('/hotels/{id}', [HotelController::class, 'update']);
    Route::delete('/hotels/{id}', [HotelController::class, 'destroy']);

    // Room Type Management - Admin only
    Route::post('/room-types', [RoomTypeController::class, 'store']);
    Route::put('/room-types/{id}', [RoomTypeController::class, 'update']);
    Route::delete('/room-types/{id}', [RoomTypeController::class, 'destroy']);

    // Room Management - Admin only
    Route::apiResource('rooms', RoomController::class);
    Route::post('/rooms/{id}/update-status', [RoomController::class, 'updateStatus']);
    Route::get('/hotels/{hotelId}/rooms', [RoomController::class, 'getHotelRooms']);
    Route::get('/rooms/statistics', [RoomController::class, 'getStatistics']);

    // User Management - Admin only
    Route::apiResource('users', UserController::class)->except(['store']); // store is public for registration

    // Advanced Booking Management - Admin only
    Route::get('/bookings/all', [BookingController::class, 'index']); // Admin can see all bookings

    // Advanced Payment Management - Admin only
    Route::get('/payments/all', [PaymentController::class, 'index']); // Admin can see all payments
});

// Hotel Manager routes (if you have hotel managers)
Route::prefix('v1')->middleware(['auth:sanctum', 'role:hotel_manager'])->group(function () {

    // Hotel managers can manage their own hotels
    Route::get('/hotels/my-hotels', [HotelController::class, 'index']); // Would need to be filtered by manager
    Route::put('/hotels/{id}', [HotelController::class, 'update']); // With ownership check

    // Room management for their hotels
    Route::get('/hotels/{hotelId}/rooms', [RoomController::class, 'getHotelRooms']);
    Route::post('/rooms/{id}/update-status', [RoomController::class, 'updateStatus']);

    // Booking management for their hotels
    Route::get('/hotels/{hotelId}/bookings', function($hotelId) {
        return app(BookingController::class)->index(request()->merge(['hotel_id' => $hotelId]));
    });

    // Room type management for their hotels
    Route::post('/room-types', [RoomTypeController::class, 'store']);
    Route::put('/room-types/{id}', [RoomTypeController::class, 'update']);
    Route::delete('/room-types/{id}', [RoomTypeController::class, 'destroy']);
});

// Additional utility routes
Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'OK',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    });

    // API Documentation route
    Route::get('/docs', function () {
        return response()->json([
            'message' => 'Hotel Booking System API',
            'version' => '1.0.0',
            'endpoints' => [
                'hotels' => '/api/v1/hotels',
                'bookings' => '/api/v1/bookings',
                'payments' => '/api/v1/payments',
                'reviews' => '/api/v1/reviews',
                'rooms' => '/api/v1/rooms',
                'room-types' => '/api/v1/room-types',
                'users' => '/api/v1/users'
            ]
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Route Model Binding Customization
|--------------------------------------------------------------------------
*/

// Custom route model binding if needed
Route::bind('review', function ($value) {
    return \App\Models\Review::findOrFail($value);
});

/*
|--------------------------------------------------------------------------
| API Routes Summary
|--------------------------------------------------------------------------
|
| PUBLIC ROUTES:
| GET    /api/v1/hotels                          - List hotels
| GET    /api/v1/hotels/{id}                     - Show hotel details
| POST   /api/v1/hotels/search                  - Search hotels
| GET    /api/v1/room-types                     - List room types
| GET    /api/v1/room-types/{id}                - Show room type details
| GET    /api/v1/rooms/available                - Get available rooms
| GET    /api/v1/reviews                        - List reviews
| GET    /api/v1/reviews/{review}               - Show review details
| GET    /api/v1/hotels/{hotelId}/reviews       - Get hotel reviews
| GET    /api/v1/reviews/statistics             - Get review statistics
| POST   /api/v1/users/register                 - User registration
|
| AUTHENTICATED USER ROUTES:
| GET    /api/v1/bookings                       - List user's bookings
| POST   /api/v1/bookings                       - Create booking
| GET    /api/v1/bookings/{id}                  - Show booking details
| PUT    /api/v1/bookings/{id}                  - Update booking
| DELETE /api/v1/bookings/{id}                  - Delete booking
| POST   /api/v1/bookings/{id}/confirm          - Confirm booking
| POST   /api/v1/bookings/{id}/cancel           - Cancel booking
|
| GET    /api/v1/payments                       - List user's payments
| POST   /api/v1/payments                       - Create payment
| GET    /api/v1/payments/{id}                  - Show payment details
| PUT    /api/v1/payments/{id}                  - Update payment
| POST   /api/v1/payments/{id}/mark-as-paid     - Mark payment as paid
| POST   /api/v1/payments/{id}/mark-as-failed   - Mark payment as failed
| POST   /api/v1/payments/{id}/mark-as-refunded - Mark payment as refunded
|
| POST   /api/v1/reviews                        - Create review
| PUT    /api/v1/reviews/{review}               - Update review
| DELETE /api/v1/reviews/{review}               - Delete review
| GET    /api/v1/reviews/user                   - Get user's reviews
| GET    /api/v1/bookings/{bookingId}/can-review - Check if can review
|
| GET    /api/v1/users/profile                  - Get user profile
| PUT    /api/v1/users/profile                  - Update user profile
|
| ADMIN ONLY ROUTES:
| POST   /api/v1/hotels                         - Create hotel
| PUT    /api/v1/hotels/{id}                    - Update hotel
| DELETE /api/v1/hotels/{id}                    - Delete hotel
|
| POST   /api/v1/room-types                     - Create room type
| PUT    /api/v1/room-types/{id}                - Update room type
| DELETE /api/v1/room-types/{id}                - Delete room type
|
| GET    /api/v1/rooms                          - List all rooms
| POST   /api/v1/rooms                          - Create room
| GET    /api/v1/rooms/{id}                     - Show room details
| PUT    /api/v1/rooms/{id}                     - Update room
| DELETE /api/v1/rooms/{id}                     - Delete room
| POST   /api/v1/rooms/{id}/update-status       - Update room status
| GET    /api/v1/hotels/{hotelId}/rooms         - Get hotel rooms
| GET    /api/v1/rooms/statistics               - Get room statistics
|
| GET    /api/v1/users                          - List all users
| GET    /api/v1/users/{id}                     - Show user details
| PUT    /api/v1/users/{id}                     - Update user
| DELETE /api/v1/users/{id}                     - Delete user
|
| GET    /api/v1/bookings/all                   - List all bookings (admin)
| GET    /api/v1/payments/all                   - List all payments (admin)
|
| UTILITY ROUTES:
| GET    /api/v1/health                         - Health check
| GET    /api/v1/docs                           - API documentation
|
|--------------------------------------------------------------------------
*/
