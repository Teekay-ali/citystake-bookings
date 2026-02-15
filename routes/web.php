<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UnitTypeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/properties', [UnitTypeController::class, 'index'])->name('properties.index');
Route::get('/properties/{building:slug}/{unitType:slug}', [UnitTypeController::class, 'show'])->name('properties.show');
Route::post('/properties/{building:slug}/{unitType:slug}/check-availability', [UnitTypeController::class, 'checkAvailability'])
    ->name('properties.check-availability');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard redirect
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Booking routes
    Route::get('/properties/{building:slug}/{unitType:slug}/book', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/properties/{building:slug}/{unitType:slug}/book', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{bookingReference}/payment', [BookingController::class, 'payment'])->name('bookings.payment');
    Route::get('/bookings/{bookingReference}/verify', [BookingController::class, 'verifyPayment'])->name('bookings.verify');
    Route::get('/bookings/{booking}/confirmation', [BookingController::class, 'confirmation'])->name('bookings.confirmation');

    // My Bookings
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // View booking details
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
});

require __DIR__.'/auth.php';
