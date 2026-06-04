<?php

namespace App\Http\Controllers;

use App\Notifications\NewBookingNotification;
use App\Notifications\BookingCancelledNotification;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use App\Models\AuditLog;
use App\Models\FinancialTransaction;
use App\Services\MonnifyService;
use App\Services\PaystackService;

use App\Models\Booking;
use App\Models\Building;
use App\Models\UnitType;
use App\Mail\BookingConfirmation;
use App\Mail\AdminNewBooking;
use App\Mail\BookingCancelled;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{

    public function index()
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->with(['building.images', 'unitType.images', 'unit'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($booking) {
                if ($booking->status === 'cancelled') return 'cancelled';
                if ($booking->status === 'paused') return 'paused';  // add this
                if ($booking->check_out < now()) return 'past';
                if ($booking->check_in <= now() && $booking->check_out >= now()) return 'active';
                return 'upcoming';
            });

        return Inertia::render('Booking/Index', [
            'bookings' => [
                'upcoming' => $bookings->get('upcoming', collect()),
                'active'   => $bookings->get('active',   collect()),
                'past'     => $bookings->get('past',      collect()),
                'cancelled'=> $bookings->get('cancelled', collect()),
                'paused'   => $bookings->get('paused',    collect()),
            ],
        ]);
    }

    public function create(Request $request, Building $building, UnitType $unitType)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $unitType->max_guests,
        ]);

        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        // Check availability
        if (!$unitType->hasAvailability($request->check_in, $request->check_out)) {
            return redirect()->back()->with('error', 'Sorry, no units are available for the selected dates. Please try different dates.');
        }

        // Calculate pricing
        $booking = new Booking([
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'guests' => $request->guests,
        ]);

        $booking->calculateTotal($unitType);

        return Inertia::render('Booking/Create', [
            'building'    => $building->load('images'),
            'unitType'    => $unitType->load('images'),
            'bookingData' => [
                'check_in'         => $request->check_in,
                'check_out'        => $request->check_out,
                'guests'           => $request->guests,
                'nights'           => $booking->nights,
                'subtotal'         => $booking->subtotal,
                'discount_type'    => $booking->discount_type,
                'discount_percent' => $booking->discount_percent,
                'discount_amount'  => $booking->discount_amount,
                'total_amount'     => $booking->total_amount,
            ],
        ]);

    }

    public function show(Booking $booking)
    {
        // Ensure user can only view their own booking
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $booking->load(['building.images', 'unitType.images', 'unit', 'messages.sender']);

        return Inertia::render('Booking/Show', [
            'booking' => $booking,
        ]);
    }

    public function store(Request $request, Building $building, UnitType $unitType)
    {
        $validated = $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:' . $unitType->max_guests,
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        // Check if unit type belongs to building
        if ($unitType->building_id !== $building->id) {
            abort(404);
        }

        try {
            $booking = DB::transaction(function () use ($validated, $building, $unitType) {
                // Lock unit type to prevent double booking
                $unitType = UnitType::lockForUpdate()->findOrFail($unitType->id);

                // Find an available unit
                $availableUnit = $unitType->findAvailableUnit($validated['check_in'], $validated['check_out']);

                if (!$availableUnit) {
                    throw new \Exception('No units available for selected dates');
                }

                $booking = new Booking([
                    'booking_reference' => Booking::generateReference(),
                    'building_id' => $building->id,
                    'unit_type_id' => $unitType->id,
                    'unit_id' => $availableUnit->id,
                    'user_id' => auth()->id(),
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'guests' => $validated['guests'],
                    'guest_name' => $validated['guest_name'],
                    'guest_email' => $validated['guest_email'],
                    'guest_phone' => $validated['guest_phone'],
                    'special_requests' => $validated['special_requests'] ?? null,
                ]);

                $unitType->loadMissing('building');
                $booking->calculateTotal($unitType);

                $booking->save();

                return $booking;
            });

            // Redirect to payment page
            return redirect()->route('bookings.payment', $booking->booking_reference)
                ->with('success', 'Booking created successfully! Proceed to payment.');

        }

        catch (\Exception $e) {
            \Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('error', 'Unable to complete your booking. Please try again or contact support.')
                ->withInput();
        }
    }

    public function payment($bookingReference)
    {
        $booking = Booking::where('booking_reference', $bookingReference)
            ->with(['building.images', 'unitType.images', 'unit'])
            ->firstOrFail();

        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if ($booking->isPaid()) {
            return redirect()->route('bookings.confirmation', $booking);
        }

        // Generate a fresh unique payment reference on every page load
        // This allows retries if the user closes the Paystack modal
        $booking->update([
            'payment_reference' => $booking->booking_reference . '-' . strtoupper(Str::random(8)),
        ]);

        $paystackService = new PaystackService();
        $monnifyService  = new MonnifyService();

        return Inertia::render('Booking/Payment', [
            'booking'             => $booking,
            'paystackPublicKey'   => $paystackService->getPublicKey(),
            'monnifyApiKey'       => $monnifyService->getApiKey(),
            'monnifyContractCode' => $monnifyService->getContractCode(),
            'monnifyTestMode'     => $monnifyService->isTestMode(),
        ]);

    }

    public function verifyPayment(Request $request, $bookingReference)
    {
        $booking = Booking::where('booking_reference', $bookingReference)->firstOrFail();

        // Ensure user can only verify their own booking
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('bookings.payment', $bookingReference)
                ->with('error', 'Payment reference not found');
        }

        try {
            $paystackService = new PaystackService();
            $response = $paystackService->verifyTransaction($reference);

            if ($response['status'] && $response['data']['status'] === 'success') {

                // Verify the amount paid matches what we expect (Paystack amounts are in kobo)
                $amountPaid = $response['data']['amount'] ?? 0;
                $amountExpected = (int) round($booking->total_amount * 100);

                if ($amountPaid < $amountExpected) {
                    \Log::warning('Paystack amount mismatch', [
                        'booking_reference' => $booking->booking_reference,
                        'expected_kobo'     => $amountExpected,
                        'paid_kobo'         => $amountPaid,
                    ]);

                    return redirect()->route('bookings.payment', $bookingReference)
                        ->with('error', 'Payment amount mismatch. Please contact support.');
                }

                $booking->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'paystack_reference' => $reference,
                    'paid_at' => now(),
                ]);

                FinancialTransaction::firstOrCreate(
                    [
                        'payment_reference' => $reference,
                        'reference_type'    => Booking::class,
                    ],
                    [
                        'building_id'     => $booking->building_id,
                        'recorded_by'     => $booking->user_id,
                        'type'            => 'income',
                        'category'        => 'booking',
                        'reference_id'    => $booking->id,
                        'description'     => "Booking {$booking->booking_reference} - {$booking->guest_name}",
                        'amount'          => $booking->total_amount,
                        'payment_method'  => 'paystack',
                        'transaction_date'=> now()->toDateString(),
                    ]
                );

                // Send confirmation email to guest
                Mail::to($booking->guest_email)->send(new BookingConfirmation($booking));

                // Send notification to admin
                $adminEmail = config('mail.admin_email', 'admin@citystake.com');
                Mail::to($adminEmail)->send(new AdminNewBooking($booking));

                $recipients = NotificationService::getUsersByRoles(['manager', 'receptionist'], $booking->building_id);
                Notification::send($recipients, new NewBookingNotification($booking));

                return redirect()->route('bookings.confirmation', $booking->id)
                    ->with('success', '🎉 Payment successful! Your booking is confirmed.');
            } else {
                return redirect()->route('bookings.payment', $bookingReference)
                    ->with('error', 'Payment verification failed. Please contact support if amount was debited.');
            }

        } catch (\Exception $e) {
            \Log::error('Payment verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('bookings.payment', $bookingReference)
                ->with('error', 'An error occurred while verifying payment.');
        }
    }

    public function verifyMonnifyPayment(Request $request, string $bookingReference)
    {
        $booking = Booking::where('booking_reference', $bookingReference)->firstOrFail();

        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if ($booking->isPaid()) {
            return redirect()->route('bookings.confirmation', $booking->id);
        }

        // Monnify returns the paymentReference in the SDK callback
        $paymentReference = $request->query('paymentReference');

        if (! $paymentReference) {
            return redirect()->route('bookings.payment', $bookingReference)
                ->with('error', 'Payment reference not found');
        }

        try {
            $monnifyService = new MonnifyService();
            $responseBody   = $monnifyService->verifyTransaction($paymentReference);

            if (($responseBody['paymentStatus'] ?? '') === 'PAID') {

                $expectedAmount = (float) $booking->total_amount;
                $paidAmount     = (float) ($responseBody['amountPaid'] ?? 0);

                if ($paidAmount < $expectedAmount) {
                    Log::warning('Monnify amount mismatch on verify', [
                        'booking_reference' => $booking->booking_reference,
                        'expected'          => $expectedAmount,
                        'paid'              => $paidAmount,
                    ]);
                    return redirect()->route('bookings.payment', $bookingReference)
                        ->with('error', 'Payment amount mismatch. Please contact support.');
                }

                $booking->update([
                    'payment_status'     => 'paid',
                    'status'             => 'confirmed',
                    'payment_method'     => 'monnify',
                    'monnify_reference'  => $responseBody['transactionReference'] ?? $paymentReference,
                    'paid_at'            => now(),
                ]);

                FinancialTransaction::firstOrCreate(
                    [
                        'payment_reference' => $paymentReference,
                        'reference_type'    => Booking::class,
                    ],
                    [
                        'building_id'     => $booking->building_id,
                        'recorded_by'     => $booking->user_id ?? 1,
                        'type'            => 'income',
                        'category'        => 'booking',
                        'reference_id'    => $booking->id,
                        'description'     => "Booking {$booking->booking_reference} - {$booking->guest_name}",
                        'amount'          => $booking->total_amount,
                        'payment_method'  => 'monnify',
                        'transaction_date'=> now()->toDateString(),
                    ]
                );

                Mail::to($booking->guest_email)->send(new BookingConfirmation($booking));

                $adminEmail = config('mail.admin_email', 'admin@citystake.com');
                Mail::to($adminEmail)->send(new AdminNewBooking($booking));

                $recipients = NotificationService::getUsersByRoles(['manager', 'receptionist'], $booking->building_id);
                Notification::send($recipients, new NewBookingNotification($booking));

                return redirect()->route('bookings.confirmation', $booking->id)
                    ->with('success', '🎉 Payment successful! Your booking is confirmed.');

            } else {
                Log::warning('Monnify payment not PAID', [
                    'paymentReference' => $paymentReference,
                    'status'           => $responseBody['paymentStatus'] ?? 'unknown',
                ]);

                return redirect()->route('bookings.payment', $bookingReference)
                    ->with('error', 'Payment not completed. Status: ' . ($responseBody['paymentStatus'] ?? 'unknown'));
            }

        } catch (\Exception $e) {
            Log::error('Monnify payment verification failed', [
                'paymentReference' => $paymentReference,
                'error'            => $e->getMessage(),
            ]);

            return redirect()->route('bookings.payment', $bookingReference)
                ->with('error', 'An error occurred while verifying payment. Please contact support.');
        }
    }

    public function confirmation(Booking $booking): Response
    {
        // Ensure user can only view their own booking
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        $booking->load(['building.primaryImage', 'unitType', 'unit']);

        return Inertia::render('Booking/Confirmation', [
            'booking' => $booking,
        ]);
    }

    public function cancel(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$booking->canBeCancelled()) {
            return redirect()->back()->with('error', 'This booking cannot be cancelled.');
        }

        $daysUntilCheckIn = now()->diffInDays($booking->check_in, false);
        $refundAmount     = 0;
        $refundNote       = '';

        // Calculate refund per Terms page policy
        $paymentMethod = $booking->payment_method;
        $isRefundable  = $booking->isPaid() && in_array($paymentMethod, ['paystack', 'monnify']);

        if ($isRefundable) {
            // Use base amount — exclude any late checkout fee that was added
            $baseAmount = $booking->total_amount - ($booking->late_checkout_fee ?? 0);

            if ($daysUntilCheckIn > 7) {
                $refundAmount = $baseAmount; // Full refund
                $refundNote   = 'Full refund applied.';
            } elseif ($daysUntilCheckIn >= 3) {
                $refundAmount = $baseAmount * 0.5;
                $refundNote   = '50% refund applied.';
            }
            // < 3 days: no refund
        }

        DB::transaction(function () use ($booking, $refundAmount, $refundNote) {
            $booking->update([
                'status'                  => 'cancelled',
                'cancelled_at'            => now(),
                'payment_status'          => $refundAmount > 0 ? 'refunded' : $booking->payment_status,
                // Reset late checkout state — fee is void on cancellation
                'late_checkout_requested' => false,
                'late_checkout_status'    => null,
                'late_checkout_fee'       => null,
                'late_checkout_approved_by'  => null,
                'late_checkout_approved_at'  => null,
                'late_checkout_settled_at'   => null,
            ]);

            AuditLog::log('booking.cancelled', $booking,
                ['status' => 'confirmed'],
                ['status' => 'cancelled', 'refund_amount' => $refundAmount]
            );

            if ($refundAmount > 0) {
                try {
                    if ($booking->payment_method === 'monnify' && $booking->monnify_reference) {
                        $monnifyService = new MonnifyService();
                        $monnifyService->refundTransaction(
                            $booking->monnify_reference,
                            $refundAmount,
                            "Booking {$booking->booking_reference} cancellation refund"
                        );
                    } elseif ($booking->paystack_reference) {
                        $paystackService = new PaystackService();
                        $paystackService->refundTransaction(
                            $booking->paystack_reference,
                            (int) ($refundAmount * 100) // convert to kobo
                        );
                    }
                } catch (\Exception $e) {
                    Log::error('Refund failed for booking ' . $booking->booking_reference, [
                        'error'          => $e->getMessage(),
                        'payment_method' => $booking->payment_method,
                    ]);
                    // Don't block the cancellation — flag it for manual review
                }
            }

        });

        // Send notifications outside the transaction so network I/O doesn't hold DB locks
        try {
            Mail::to($booking->guest_email)->send(new BookingCancelled($booking));
        } catch (\Exception $e) {
            Log::error('Cancellation email failed for booking ' . $booking->booking_reference, ['error' => $e->getMessage()]);
        }

        $recipients = NotificationService::getUsersByRoles(['manager', 'receptionist'], $booking->building_id);
        Notification::send($recipients, new BookingCancelledNotification($booking));

        $message = 'Booking cancelled successfully.';
        if ($refundNote) {
            $message .= ' ' . $refundNote . ' Please allow 5–7 business days.';
        }

        return redirect()->route('bookings.index')->with('success', $message);
    }

    public function downloadInvoice(Booking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$booking->isPaid()) {
            abort(404, 'Invoice only available for paid bookings.');
        }

        return InvoiceService::download($booking);
    }

}
