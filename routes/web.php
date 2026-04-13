<?php

use App\Http\Controllers\Admin\AvailabilityController;
use App\Http\Controllers\Admin\BlockedDateController;
use App\Http\Controllers\Admin\BookingExportController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Profile\EmailPreferencesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\UnitTypeController as AdminUnitTypeController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\MaintenanceReportController;
use App\Http\Controllers\Admin\ProcurementController;
use App\Http\Controllers\Admin\StaffQueryController;
use App\Http\Controllers\Admin\StockController;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsStaff;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'name'    => 'required|string|max:255',
        'email'   => 'required|email|max:255',
        'phone'   => 'nullable|string|max:20',
        'subject' => 'required|string|max:255',
        'message' => 'required|string|max:2000',
    ]);

    $adminEmail = config('mail.help_email');

    \Illuminate\Support\Facades\Mail::send(
        [],
        [],
        function ($mail) use ($validated, $adminEmail) {
            $mail->to($adminEmail)
                ->replyTo($validated['email'], $validated['name'])
                ->subject('Contact Form: ' . $validated['subject'])
                ->html(
                    '<p><strong>From:</strong> ' . e($validated['name']) . ' &lt;' . e($validated['email']) . '&gt;</p>' .
                    ($validated['phone'] ? '<p><strong>Phone:</strong> ' . e($validated['phone']) . '</p>' : '') .
                    '<p><strong>Subject:</strong> ' . e($validated['subject']) . '</p>' .
                    '<hr><p>' . nl2br(e($validated['message'])) . '</p>'
                );
        }
    );

    return redirect()->back()->with('success', 'Thank you for contacting us! We\'ll get back to you soon.');
})->name('contact.store');


Route::get('/terms', function () {
    return Inertia::render('Terms');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');

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
    Route::patch('/profile/email-preferences', [EmailPreferencesController::class, 'update'])->name('profile.email-preferences.update');

    // Booking routes
    Route::get('/properties/{building:slug}/{unitType:slug}/book', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/properties/{building:slug}/{unitType:slug}/book', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{bookingReference}/payment', [BookingController::class, 'payment'])->name('bookings.payment');
    Route::get('/bookings/{bookingReference}/verify', [BookingController::class, 'verifyPayment'])->name('bookings.verify');
    Route::get('/bookings/{booking}/confirmation', [BookingController::class, 'confirmation'])->name('bookings.confirmation');
    Route::post('/bookings/{booking}/check-in', [AdminBookingController::class, 'checkIn'])->name('bookings.check-in');

    // My Bookings
    Route::get('/my-bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // View booking details
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
});



// Admin routes
Route::middleware(['auth', EnsureUserIsStaff::class])->prefix('manage')->name('manage.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Availability Board
    Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');

    // Bookings
    Route::get('/bookings/late-checkout-requests', [AdminBookingController::class, 'lateCheckoutRequests'])->name('bookings.late-checkout.index');
    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/create', [AdminBookingController::class, 'create'])->name('bookings.create');
    Route::get('/bookings/calendar', [App\Http\Controllers\Admin\BookingCalendarController::class, 'index'])->name('bookings.calendar');
    Route::get('/bookings/export', [BookingExportController::class, 'export'])->name('bookings.export');
    Route::post('/bookings', [AdminBookingController::class, 'storeAdminBooking'])->name('bookings.store');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/check-in', [AdminBookingController::class, 'checkIn'])->name('bookings.check-in');

    Route::post('/bookings/{booking}/late-checkout/request', [AdminBookingController::class, 'requestLateCheckout'])->name('bookings.late-checkout.request');
    Route::post('/bookings/{booking}/late-checkout/approve', [AdminBookingController::class, 'approveLateCheckout'])->name('bookings.late-checkout.approve');
    Route::post('/bookings/{booking}/late-checkout/settle', [AdminBookingController::class, 'settleLateCheckout'])->name('bookings.late-checkout.settle');

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

    // Analytics
    Route::get('/analytics/occupancy', [App\Http\Controllers\Admin\OccupancyAnalyticsController::class, 'index'])
        ->name('analytics.occupancy');

    // Staff Management
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::post('/staff/{staff}/toggle-active', [StaffController::class, 'toggleActive'])->name('staff.toggle-active');

    // Vendors
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
    Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
    Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
    Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
    Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');

    // Complaints
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('complaints.resolve');
    Route::delete('/complaints/{complaint}', [ComplaintController::class, 'destroy'])->name('complaints.destroy');

    // Maintenance Reports
    Route::get('/maintenance', [MaintenanceReportController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/create', [MaintenanceReportController::class, 'create'])->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceReportController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/{maintenance}', [MaintenanceReportController::class, 'show'])->name('maintenance.show');
    Route::post('/maintenance/{maintenance}/approve', [MaintenanceReportController::class, 'approve'])->name('maintenance.approve');
    Route::delete('/maintenance/{maintenance}', [MaintenanceReportController::class, 'destroy'])->name('maintenance.destroy');

    // Procurement
    Route::get('/procurement', [ProcurementController::class, 'index'])->name('procurement.index');
    Route::get('/procurement/create', [ProcurementController::class, 'create'])->name('procurement.create');
    Route::post('/procurement', [ProcurementController::class, 'store'])->name('procurement.store');
    Route::get('/procurement/{procurement}', [ProcurementController::class, 'show'])->name('procurement.show');
    Route::post('/procurement/{procurement}/approve', [ProcurementController::class, 'approve'])->name('procurement.approve');
    Route::delete('/procurement/{procurement}', [ProcurementController::class, 'destroy'])->name('procurement.destroy');


    // Staff Queries
    Route::get('/staff-queries', [StaffQueryController::class, 'index'])->name('staff-queries.index');
    Route::get('/staff-queries/create', [StaffQueryController::class, 'create'])->name('staff-queries.create');
    Route::post('/staff-queries', [StaffQueryController::class, 'store'])->name('staff-queries.store');
    Route::get('/staff-queries/{staffQuery}', [StaffQueryController::class, 'show'])->name('staff-queries.show');
    Route::post('/staff-queries/{staffQuery}/resolve', [StaffQueryController::class, 'resolve'])->name('staff-queries.resolve');
    Route::delete('/staff-queries/{staffQuery}', [StaffQueryController::class, 'destroy'])->name('staff-queries.destroy');

    // Stock Keeping
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/create', [StockController::class, 'create'])->name('stock.create');
    Route::post('/stock', [StockController::class, 'store'])->name('stock.store');
    Route::get('/stock/{stock}', [StockController::class, 'show'])->name('stock.show');
    Route::post('/stock/{stock}/log', [StockController::class, 'logUsage'])->name('stock.log');
    Route::get('/stock/{stock}/edit', [StockController::class, 'edit'])->name('stock.edit');
    Route::put('/stock/{stock}', [StockController::class, 'update'])->name('stock.update');

});

require __DIR__.'/auth.php';
