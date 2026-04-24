<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Mail\AdminNewBooking;
use App\Mail\BookingConfirmation;
use App\Models\Booking;
use App\Models\FinancialTransaction;
use App\Notifications\NewBookingNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class MonnifyWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // ── 1. Verify Monnify HMAC signature ─────────────────────────
        $secret    = config('services.monnify.secret_key');
        $hash      = $request->header('monnify-signature');
        $payload   = $request->getContent();

        $computed = hash('sha512', $payload . $secret);

        if (! hash_equals($computed, (string) $hash)) {
            Log::warning('Monnify webhook: invalid signature');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // ── 2. Decode payload ─────────────────────────────────────────
        $data = $request->json()->all();

        $eventType = $data['eventType'] ?? null;

        // Only process successful payment events
        if ($eventType !== 'SUCCESSFUL_TRANSACTION') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        $body             = $data['eventData'] ?? [];
        $paymentReference = $body['paymentReference'] ?? null;
        $paymentStatus    = $body['paymentStatus'] ?? null;
        $transactionRef   = $body['transactionReference'] ?? $paymentReference;
        $amountPaid       = $body['amountPaid'] ?? 0;

        if (! $paymentReference || $paymentStatus !== 'PAID') {
            return response()->json(['message' => 'Not a paid event'], 200);
        }

        // ── 3. Find booking by payment_reference ──────────────────────
        // payment_reference is refreshed on every Payment page load and used
        // as the `reference` passed to the Monnify SDK
        $booking = Booking::where('payment_reference', $paymentReference)
            ->orWhere('monnify_reference', $transactionRef)
            ->first();

        if (! $booking) {
            Log::warning('Monnify webhook: booking not found', compact('paymentReference'));
            return response()->json(['message' => 'Booking not found'], 200);
        }

        // ── 4. Idempotency guard ──────────────────────────────────────
        if ($booking->payment_status === 'paid') {
            return response()->json(['message' => 'Already processed'], 200);
        }

        // ── 5. Amount validation ──────────────────────────────────────
        $expectedAmount = (float) $booking->total_amount;
        $paidAmount     = (float) $amountPaid;

        if ($paidAmount < $expectedAmount) {
            Log::error('Monnify webhook: amount mismatch', [
                'booking_reference' => $booking->booking_reference,
                'expected'          => $expectedAmount,
                'paid'              => $paidAmount,
            ]);
            return response()->json(['message' => 'Amount mismatch'], 200);
        }

        // ── 6. Confirm booking ────────────────────────────────────────
        $booking->update([
            'payment_status'    => 'paid',
            'status'            => 'confirmed',
            'payment_method'    => 'monnify',
            'monnify_reference' => $transactionRef,
            'paid_at'           => now(),
        ]);

        // ── 7. Financial transaction ──────────────────────────────────
        FinancialTransaction::firstOrCreate(
            ['payment_reference' => $paymentReference, 'reference_type' => Booking::class],
            [
                'building_id'    => $booking->building_id,
                'recorded_by'    => $booking->user_id ?? 1,
                'type'           => 'income',
                'category'       => 'booking',
                'reference_id'   => $booking->id,
                'description'    => "Booking {$booking->booking_reference} — {$booking->guest_name}",
                'amount'         => $booking->total_amount,
                'payment_method' => 'monnify',
                'transaction_date' => now()->toDateString(),
            ]
        );

        // ── 8. Notifications & emails ─────────────────────────────────
        try {
            $booking->loadMissing(['building', 'unitType', 'unit']);
            Mail::to($booking->guest_email)->send(new BookingConfirmation($booking));
        } catch (\Exception $e) {
            Log::error('Monnify webhook: confirmation email failed', [
                'booking_reference' => $booking->booking_reference,
                'error'             => $e->getMessage(),
            ]);
        }

        try {
            $adminEmail = config('mail.admin_email', 'admin@citystake.com');
            Mail::to($adminEmail)->send(new AdminNewBooking($booking));
        } catch (\Exception $e) {
            Log::error('Monnify webhook: admin email failed', ['error' => $e->getMessage()]);
        }

        try {
            $recipients = NotificationService::getUsersByRoles(
                ['manager', 'receptionist'],
                $booking->building_id
            );
            Notification::send($recipients, new NewBookingNotification($booking));
        } catch (\Exception $e) {
            Log::error('Monnify webhook: push notification failed', ['error' => $e->getMessage()]);
        }

        Log::info('Monnify webhook: booking confirmed via webhook', [
            'booking_reference' => $booking->booking_reference,
            'transaction_ref'   => $transactionRef,
        ]);

        return response()->json(['message' => 'OK'], 200);
    }
}
