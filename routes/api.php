<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\AdminController;

Route::get('/login', function () {
    return response()->json([
        'message' => 'Utilisez POST /api/login avec email + password.',
        'test_admin' => ['email' => 'contact@coutureatelier.com', 'password' => 'password'],
        'test_client' => ['email' => 'client@test.com', 'password' => 'password'],
    ]);
});

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Public
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/reservations/available-slots', [ReservationController::class, 'availableSlots']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::get('/reviews', [ReviewController::class, 'index']);

// Orders (sans auth nécessaire) + Promo validation
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/promo/validate', [OrderController::class, 'validatePromo']);

// Settings public
Route::get('/settings', [AdminController::class, 'getSettings']);

// Auth required
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::patch('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    // Admin only
    Route::get('/admin/reservations', [ProfileController::class, 'allReservations']);
    Route::get('/admin/users', [ProfileController::class, 'allUsers']);

    Route::post('/admin/services', [ServiceController::class, 'store']);
    Route::put('/admin/services/{service}', [ServiceController::class, 'update']);
    Route::patch('/admin/services/{service}/toggle', [ServiceController::class, 'toggle']);
    Route::delete('/admin/services/{service}', [ServiceController::class, 'destroy']);

    Route::post('/admin/products', [ProductController::class, 'store']);
    Route::put('/admin/products/{product}', [ProductController::class, 'update']);
    Route::patch('/admin/products/{product}/toggle', [ProductController::class, 'toggle']);
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/admin/products/{product}/image', [ProductController::class, 'uploadImage']);
    Route::post('/admin/products/{product}/images', [AdminController::class, 'uploadImages']);
    Route::delete('/admin/products/{product}/images/{index}', [AdminController::class, 'deleteImage']);

    Route::get('/admin/promo-codes', [AdminController::class, 'getPromoCodes']);
    Route::post('/admin/promo-codes', [AdminController::class, 'savePromoCode']);
    Route::delete('/admin/promo-codes/{id}', [AdminController::class, 'deletePromoCode']);

    Route::get('/admin/orders', [AdminController::class, 'getOrders']);
    Route::patch('/admin/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);

    Route::post('/admin/settings', [AdminController::class, 'saveSettings']);
});
