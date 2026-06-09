<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BookingController;
use App\Models\User;

Route::get('/', fn () => view('home'))->name('home');
Route::get('/gallery', fn () => view('gallery'))->name('gallery');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // 1. Dashboard Route
    Route::get('/dashboard', function () {
        /** @var User $user */
        $user = Auth::user();
        $bookings = $user->bookings()->with('court')->latest()->get();
        return view('dashboard', compact('bookings'));
    })->name('dashboard');

    // 2. Booking Form
    Route::get('/bookings/create', [BookingController::class, 'create'])->name('bookings.create');

    // 3. Store Booking (Saves to Session)
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');

    // 4. Checkout Page (Retrieves from Session)
    Route::get('/bookings/checkout', [BookingController::class, 'checkout'])->name('bookings.checkout');

    // 5. Process Payment (Saves Session to Database)
    Route::post('/bookings/pay', [BookingController::class, 'pay'])->name('bookings.pay');

});

require __DIR__.'/settings.php';
