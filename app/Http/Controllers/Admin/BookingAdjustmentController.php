<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingAdjustmentNotification;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingAdjustment;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BookingAdjustmentController extends Controller
{
    public function store(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()->can('manage-adjustments'), 403);

        abort_unless(
            in_array($booking->status, ['confirmed', 'checked_in', 'completed']),
            422,
            'Adjustments can only be applied to confirmed, checked-in, or completed bookings.'
        );

        $validated = $request->validate([
            'amount_type'       => 'required|in:fixed,percentage',
            'amount_value'      => 'required|numeric|min:0.01',
            'reason'            => 'required|string|max:255',
            'notes'             => 'nullable|string|max:1000',
            'payment_reference' => 'nullable|string|max:255',
            'transaction_date'  => 'required|date',
        ]);

        if ($validated['amount_type'] === 'percentage') {
            abort_if($validated['amount_value'] > 100, 422, 'Percentage cannot exceed 100%.');
            $amountNaira = round(($validated['amount_value'] / 100) * $booking->total_amount, 2);
        } else {
            $amountNaira = $validated['amount_value'];
        }

        $adjustment = BookingAdjustment::create([
            'booking_id'        => $booking->id,
            'applied_by'        => auth()->id(),
            'amount_type'       => $validated['amount_type'],
            'amount_value'      => $validated['amount_value'],
            'amount_naira'      => $amountNaira,
            'reason'            => $validated['reason'],
            'notes'             => $validated['notes'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'transaction_date'  => $validated['transaction_date'],
        ]);

        // Record as an expense in the financial ledger
        FinancialTransaction::create([
            'building_id'       => $booking->building_id,
            'recorded_by'       => auth()->id(),
            'type'              => 'expense',
            'category'          => 'goodwill_adjustment',
            'reference_type'    => BookingAdjustment::class,
            'reference_id'      => $adjustment->id,
            'description'       => "Goodwill adjustment — {$booking->booking_reference} ({$booking->guest_name})",
            'amount'            => $amountNaira,
            'payment_method'    => 'bank_transfer',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'transaction_date'  => $validated['transaction_date'],
            'notes'             => $validated['notes'] ?? null,
        ]);

        AuditLog::log('booking.adjustment_applied', $booking, null, [
            'amount_naira'  => $amountNaira,
            'amount_type'   => $validated['amount_type'],
            'amount_value'  => $validated['amount_value'],
            'reason'        => $validated['reason'],
            'applied_by'    => auth()->id(),
        ]);

        // Notify guest
        try {
            $adjustment->load('booking.building');
            Mail::to($booking->guest_email)->send(new BookingAdjustmentNotification($adjustment));
        } catch (\Exception $e) {
            \Log::error('Failed to send adjustment notification', ['error' => $e->getMessage(), 'booking_id' => $booking->id]);
        }

        return back()->with('success', '₦' . number_format($amountNaira, 0) . ' goodwill adjustment applied and guest notified.');
    }

    public function destroy(Booking $booking, BookingAdjustment $adjustment)
    {
        abort_unless(auth()->user()->can('manage-adjustments'), 403);
        abort_unless($adjustment->booking_id === $booking->id, 403);

        // Remove the linked financial transaction
        FinancialTransaction::where('reference_type', BookingAdjustment::class)
            ->where('reference_id', $adjustment->id)
            ->delete();

        AuditLog::log('booking.adjustment_deleted', $booking, [
            'amount_naira' => $adjustment->amount_naira,
            'reason'       => $adjustment->reason,
        ], null);

        $adjustment->delete();

        return back()->with('success', 'Adjustment removed.');
    }
}
