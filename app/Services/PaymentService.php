<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\CautionFeeCharge;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The one place booking money is recorded. Every method books its income (or
 * fee) and updates the related record inside a single transaction, so a
 * financial transaction can never exist without its matching state change.
 */
class PaymentService
{
    /**
     * Record a payment against a booking: books the income, increases
     * amount_received, and rolls payment_status forward to partial/paid.
     * Returns the amount actually applied (never overpays the balance).
     */
    public function recordPayment(Booking $booking, float $amount, string $method, ?string $reference, string $description): float
    {
        $amount = round(min($amount, max(0, (float) $booking->total_amount - (float) $booking->amount_received)), 2);
        if ($amount <= 0) {
            return 0.0;
        }

        return DB::transaction(function () use ($booking, $amount, $method, $reference, $description) {
            FinancialTransaction::create([
                'building_id'      => $booking->building_id,
                'recorded_by'      => auth()->id(),
                'type'             => 'income',
                'category'         => 'booking',
                'reference_type'   => Booking::class,
                'reference_id'     => $booking->id,
                'description'      => $description,
                'amount'           => $amount,
                'payment_method'   => $method,
                'payment_reference'=> $reference,
                'transaction_date' => now()->toDateString(),
            ]);

            $received  = round((float) $booking->amount_received + $amount, 2);
            $fullyPaid = $received >= (float) $booking->total_amount - 0.01;

            $booking->update([
                'amount_received'    => $received,
                'payment_status'     => $fullyPaid ? 'paid' : 'partial',
                'paid_at'            => $fullyPaid ? ($booking->paid_at ?? now()) : $booking->paid_at,
                'payment_method'     => $booking->payment_method ?? $method,
                'paystack_reference' => $booking->paystack_reference ?? $reference,
            ]);

            return $amount;
        });
    }

    /** Mark a weekly installment paid and write its income transaction. */
    public function settleInstallment(BookingInstallment $installment, string $method, ?string $reference = null): void
    {
        if ($installment->paid_at) {
            return;
        }

        $booking = $installment->booking;

        DB::transaction(function () use ($installment, $booking, $method, $reference) {
            $txn = FinancialTransaction::create([
                'building_id'      => $booking->building_id,
                'recorded_by'      => auth()->id(),
                'type'             => 'income',
                'category'         => 'booking',
                'reference_type'   => Booking::class,
                'reference_id'     => $booking->id,
                'description'      => "Weekly payment (week {$installment->week_number}) - {$booking->booking_reference} · {$booking->guest_name}",
                'amount'           => $installment->amount,
                'payment_method'   => $method,
                'payment_reference'=> $reference,
                'transaction_date' => now()->toDateString(),
            ]);

            $installment->update([
                'paid_at'                  => now(),
                'recorded_by'              => auth()->id(),
                'financial_transaction_id' => $txn->id,
            ]);
        });
    }

    /** Settle a late-checkout fee: mark the booking settled and book the income. */
    public function settleLateCheckout(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking->update([
                'late_checkout_settled_at' => now(),
                'late_checkout_status'     => 'settled',
            ]);

            FinancialTransaction::create([
                'building_id'      => $booking->building_id,
                'recorded_by'      => auth()->id(),
                'type'             => 'income',
                'category'         => 'late_checkout',
                'reference_type'   => Booking::class,
                'reference_id'     => $booking->id,
                'description'      => "Late checkout fee - {$booking->guest_name} ({$booking->booking_reference})",
                'amount'           => $booking->late_checkout_fee,
                'payment_method'   => 'cash',
                'transaction_date' => now()->toDateString(),
            ]);
        });
    }

    // ── Caution fee ─────────────────────────────────────────────

    /** Draw an in-stay charge against the caution fee: book income + record the charge. */
    public function chargeCaution(Booking $booking, string $category, string $description, float $amount, User $by): CautionFeeCharge
    {
        $charge = null;

        DB::transaction(function () use (&$charge, $booking, $category, $description, $amount, $by) {
            $txn = FinancialTransaction::create([
                'building_id'      => $booking->building_id,
                'recorded_by'      => $by->id,
                'type'             => 'income',
                'category'         => CautionFeeCharge::INCOME_CATEGORY[$category],
                'reference_type'   => Booking::class,
                'reference_id'     => $booking->id,
                'description'      => CautionFeeCharge::CATEGORIES[$category]
                    . " - {$booking->guest_name} ({$booking->booking_reference}): {$description}",
                'amount'           => $amount,
                'payment_method'   => 'caution_fee',
                'transaction_date' => now()->toDateString(),
            ]);

            $charge = $booking->cautionCharges()->create([
                'category'                 => $category,
                'description'              => $description,
                'amount'                   => $amount,
                'recorded_by'              => $by->id,
                'financial_transaction_id' => $txn->id,
            ]);
        });

        return $charge;
    }

    /** Void a caution charge: reverse the recognised income and flag the charge. */
    public function voidCautionCharge(CautionFeeCharge $charge, string $reason, User $by): void
    {
        DB::transaction(function () use ($charge, $reason, $by) {
            $charge->financialTransaction?->delete();

            $charge->update([
                'voided_at'                => now(),
                'voided_by'                => $by->id,
                'void_reason'              => $reason,
                'financial_transaction_id' => null,
            ]);
        });
    }

    /**
     * Settle the caution fee at checkout: mark it processed and, when the guest
     * forfeits part of the remaining balance, book that deduction as income.
     */
    public function settleCautionRefund(Booking $booking, float $totalKept, float $deduction, ?string $reason, User $by): void
    {
        DB::transaction(function () use ($booking, $totalKept, $deduction, $reason, $by) {
            $booking->update([
                'caution_fee_refunded'         => true,
                'caution_fee_refunded_at'      => now(),
                'caution_fee_refunded_by'      => $by->id,
                'caution_fee_deduction'        => $totalKept > 0 ? $totalKept : null,
                'caution_fee_deduction_reason' => $reason,
            ]);

            if ($deduction > 0) {
                FinancialTransaction::create([
                    'building_id'      => $booking->building_id,
                    'recorded_by'      => $by->id,
                    'type'             => 'income',
                    'category'         => 'caution_fee_deduction',
                    'reference_type'   => Booking::class,
                    'reference_id'     => $booking->id,
                    'description'      => "Caution fee deduction - {$booking->guest_name} ({$booking->booking_reference})"
                        . ($reason ? ": {$reason}" : ''),
                    'amount'           => $deduction,
                    'payment_method'   => 'cash',
                    'transaction_date' => now()->toDateString(),
                ]);
            }
        });
    }
}
