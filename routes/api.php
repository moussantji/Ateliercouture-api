<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ProfileController;

/*
|--------------------------------------------------------------------------
| API Routes - CoutureAtelier
|--------------------------------------------------------------------------
*/

// 🧑‍💻 Authentification
Route::get('/login', function () {
    return response()->json([
        'message' => 'Utilisez POST /api/login avec email + password.',
        'test_admin' => ['email' => 'contact@coutureatelier.com', 'password' => 'password'],
        'test_client' => ['email' => 'client@test.com', 'password' => 'password'],
    ]);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// ✂️ Services
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);

// 🛍️ Produits
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// 📅 Réservations
Route::get('/reservations/available-slots', [ReservationController::class, 'availableSlots']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::get('/reservations', [ReservationController::class, 'index'])->middleware('auth:sanctum');
Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->middleware('auth:sanctum');
Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->middleware('auth:sanctum');
Route::patch('/reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->middleware('auth:sanctum');

// 💬 Avis
Route::get('/reviews', [ReviewController::class, 'index']);

// 👤 Profil
Route::get('/profile', [ProfileController::class, 'show'])->middleware('auth:sanctum');
Route::put('/profile', [ProfileController::class, 'update'])->middleware('auth:sanctum');

// 🔐 Admin (réservé aux admins)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/reservations', [ProfileController::class, 'allReservations']);
    Route::get('/admin/users', [ProfileController::class, 'allUsers']);
    
    // Admin - Services
    Route::post('/admin/services', [ServiceController::class, 'store']);
    Route::put('/admin/services/{service}', [ServiceController::class, 'update']);
    Route::patch('/admin/services/{service}/toggle', [ServiceController::class, 'toggle']);
    Route::delete('/admin/services/{service}', [ServiceController::class, 'destroy']);
    
    // Admin - Produits
    Route::post('/admin/products', [ProductController::class, 'store']);
    Route::put('/admin/products/{product}', [ProductController::class, 'update']);
    Route::patch('/admin/products/{product}/toggle', [ProductController::class, 'toggle']);
    Route::delete('/admin/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/admin/products/{product}/image', [ProductController::class, 'uploadImage']);
});
