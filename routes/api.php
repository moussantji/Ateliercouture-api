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
Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->middleware('auth:sanctum');

// 💬 Avis
Route::get('/reviews', [ReviewController::class, 'index']);

// 👤 Profil
Route::get('/profile', [ProfileController::class, 'show']);
Route::put('/profile', [ProfileController::class, 'update']);

// 🔐 Admin (réservé aux admins)
Route::get('/admin/reservations', [ProfileController::class, 'allReservations'])->middleware('auth:sanctum');
Route::get('/admin/users', [ProfileController::class, 'allUsers'])->middleware('auth:sanctum');
