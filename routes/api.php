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
    return response()->json(['message'=>'Utilisez POST /api/login', 'test_admin'=>['email'=>'contact@coutureatelier.com','password'=>'password'],'test_client'=>['email'=>'client@test.com','password'=>'password']]);
});

Route::post('/register', [AuthController::class,'register'])->middleware('throttle:registration');
Route::post('/login', [AuthController::class,'login'])->middleware('throttle:login');
Route::post('/auth/google', [AuthController::class,'google'])->middleware('throttle:google-auth');
Route::post('/logout', [AuthController::class,'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class,'user'])->middleware('auth:sanctum');

Route::get('/services', [ServiceController::class,'index']);
Route::get('/services/{service}', [ServiceController::class,'show']);
Route::get('/products', [ProductController::class,'index']);
Route::get('/products/featured', [ProductController::class,'featured']);
Route::get('/products/{product}', [ProductController::class,'show']);
Route::get('/reservations/available-slots', [ReservationController::class,'availableSlots']);
Route::post('/reservations', [ReservationController::class,'store']);
Route::get('/reviews', [ReviewController::class,'index']);

Route::post('/orders', [OrderController::class,'store']);
Route::get('/promo/validate', [OrderController::class,'validatePromo']);
Route::get('/settings', [AdminController::class,'getSettings']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reservations', [ReservationController::class,'index']);
    Route::get('/reservations/{reservation}', [ReservationController::class,'show']);
    Route::get('/orders', [OrderController::class,'index']);
    Route::delete('/reservations/{reservation}', [ReservationController::class,'destroy']);
    Route::patch('/reservations/{reservation}/confirm', [ReservationController::class,'confirm']);
    Route::get('/profile', [ProfileController::class,'show']);
    Route::put('/profile', [ProfileController::class,'update']);

    // Every back-office operation requires an authenticated administrator.
    // Controller checks remain as defense in depth for direct reuse.
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/reservations', [ProfileController::class,'allReservations']);
        Route::get('/users', [ProfileController::class,'allUsers']);
        Route::get('/categories', [AdminController::class,'getCategories']);

        Route::post('/services', [ServiceController::class,'store']);
        Route::put('/services/{service}', [ServiceController::class,'update']);
        Route::patch('/services/{service}/toggle', [ServiceController::class,'toggle']);
        Route::delete('/services/{service}', [ServiceController::class,'destroy']);

        Route::post('/products', [ProductController::class,'store']);
        Route::put('/products/{product}', [ProductController::class,'update']);
        Route::patch('/products/{product}/toggle', [ProductController::class,'toggle']);
        Route::delete('/products/{product}', [ProductController::class,'destroy']);
        Route::post('/products/{product}/image', [ProductController::class,'uploadImage']);
        Route::post('/products/{product}/images', [AdminController::class,'uploadImages']);
        Route::delete('/products/{product}/images/{index}', [AdminController::class,'deleteImage']);

        Route::get('/promo-codes', [AdminController::class,'getPromoCodes']);
        Route::post('/promo-codes', [AdminController::class,'savePromoCode']);
        Route::delete('/promo-codes/{id}', [AdminController::class,'deletePromoCode']);

        Route::get('/orders', [AdminController::class,'getOrders']);
        Route::patch('/orders/{id}/status', [AdminController::class,'updateOrderStatus']);
        Route::post('/settings', [AdminController::class,'saveSettings']);
    });
});
