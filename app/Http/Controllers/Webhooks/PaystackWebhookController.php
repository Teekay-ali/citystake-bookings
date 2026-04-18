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
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        // 1. Verify the signature
        $signature = $request->header('x-paystack-signature');
        $secret    = config('services.paystack.secret_key');

        if (!$signature || $signature !== hash_hmac('sha512', $request->getContent(), $secret)) {
            Log::warning('Paystack webhook: invalid signature');
            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();
        $event   = $payload['event'] ?? null;

        Log::info('Paystack webhook received', ['event' => $event]);

        if ($event === 'charge.success') {
            $this->handleChargeSuccess($payload['data'] ?? []);
        }

        // Always return 200 so Paystack stops retrying
        return response('OK', 200);
    }

    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            Log::warning('Paystack webhook: charge.success missing reference');
            return;
        }

        // Find the booking by payment_reference
        $booking = Booking::where('payment_reference', $reference)
            ->orWhere('paystack_reference', $reference)
            ->first();

        if (!$booking) {
            Log::warning('Paystack webhook: no booking found for reference', ['reference' => $reference]);
            return;
        }

        // Idempotency check — don't process twice
        if ($booking->isPaid()) {
            Log::info('Paystack webhook: booking already paid, skipping', [
                'booking_reference' => $booking->booking_reference,
            ]);
            return;
        }

        $booking->update([
            'payment_status'     => 'paid',
            'status'             => 'confirmed',
            'paystack_reference' => $reference,
            'paid_at'            => now(),
        ]);

        FinancialTransaction::create([
            'building_id'       => $booking->building_id,
            'recorded_by'       => $booking->user_id ?? 1,
            'type'              => 'income',
            'category'          => 'booking',
            'reference_type'    => Booking::class,
            'reference_id'      => $booking->id,
            'description'       => "Booking {$booking->booking_reference} — {$booking->guest_name} (webhook)",
            'amount'            => $booking->total_amount,
            'payment_method'    => 'paystack',
            'payment_reference' => $reference,
            'transaction_date'  => now()->toDateString(),
        ]);

        try {
            Mail::to($booking->guest_email)->send(new BookingConfirmation($booking->load(['building', 'unitType', 'unit'])));
            $adminEmail = config('mail.admin_email', 'admin@citystake.com');
            Mail::to($adminEmail)->send(new AdminNewBooking($booking));

            $recipients = NotificationService::getUsersByRoles(['manager', 'receptionist'], $booking->building_id);
            Notification::send($recipients, new NewBookingNotification($booking));
        } catch (\Exception $e) {
            // Don't fail the webhook response because mail failed
            Log::error('Paystack webhook: post-payment notification failed', [
                'booking_reference' => $booking->booking_reference,
                'error'             => $e->getMessage(),
            ]);
        }

        Log::info('Paystack webhook: booking confirmed via webhook', [
            'booking_reference' => $booking->booking_reference,
        ]);
    }
}
