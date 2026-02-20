<?php

use App\Http\Controllers\Admin\BlockedDateController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\UnitTypeController as AdminUnitTypeController;

use App\Http\Middleware\EnsureUserIsAdmin;
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



// Admin routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Bookings
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [AdminBookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [AdminBookingController::class, 'storeAdminBooking'])->name('bookings.store');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');

    // Properties
    Route::get('/properties', [BuildingController::class, 'index'])->name('properties.index');
    Route::get('/properties/create', [BuildingController::class, 'create'])->name('properties.create');
    Route::post('/properties', [BuildingController::class, 'store'])->name('properties.store');
    Route::get('/properties/{building}/edit', [BuildingController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{building}', [BuildingController::class, 'update'])->name('properties.update');
    Route::delete('/properties/{building}', [BuildingController::class, 'destroy'])->name('properties.destroy');

    // Unit Types
    Route::get('/properties/{building}/unit-types/create', [AdminUnitTypeController::class, 'create'])->name('unit-types.create');
    Route::post('/properties/{building}/unit-types', [AdminUnitTypeController::class, 'store'])->name('unit-types.store');
    Route::get('/properties/{building}/unit-types/{unitType}/edit', [AdminUnitTypeController::class, 'edit'])->name('unit-types.edit');
    Route::put('/properties/{building}/unit-types/{unitType}', [AdminUnitTypeController::class, 'update'])->name('unit-types.update');
    Route::delete('/properties/{building}/unit-types/{unitType}', [AdminUnitTypeController::class, 'destroy'])->name('unit-types.destroy');

    // Blocked Dates
    Route::get('/blocked-dates', [BlockedDateController::class, 'index'])->name('blocked-dates.index');
    Route::get('/blocked-dates/create', [BlockedDateController::class, 'create'])->name('blocked-dates.create');
    Route::post('/blocked-dates', [BlockedDateController::class, 'store'])->name('blocked-dates.store');
    Route::delete('/blocked-dates/{blockedDate}', [BlockedDateController::class, 'destroy'])->name('blocked-dates.destroy');

});



require __DIR__.'/auth.php';
