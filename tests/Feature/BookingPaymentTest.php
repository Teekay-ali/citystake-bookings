<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function actingStaff(): User
    {
        $user = User::factory()->create(['is_staff' => true]);
        $this->actingAs($user);

        return $user;
    }

    public function test_recording_a_partial_payment_books_income_and_credits_the_booking(): void
    {
        $this->actingStaff();
        $booking = Booking::factory()->create(['total_amount' => 100000, 'amount_received' => 0]);

        $applied = $booking->recordPayment(40000, 'pos', 'REF-1', 'Deposit');

        $this->assertSame(40000.0, $applied);
        $booking->refresh();
        $this->assertEquals(40000, $booking->amount_received);
        $this->assertSame('partial', $booking->payment_status);

        // Exactly one income transaction, matching the amount and linked to the booking.
        $txns = FinancialTransaction::where('reference_type', Booking::class)
            ->where('reference_id', $booking->id)->get();
        $this->assertCount(1, $txns);
        $this->assertEquals(40000, $txns->first()->amount);
        $this->assertSame('income', $txns->first()->type);
    }

    public function test_full_payment_marks_the_booking_paid(): void
    {
        $this->actingStaff();
        $booking = Booking::factory()->create(['total_amount' => 100000, 'amount_received' => 0]);

        $booking->recordPayment(100000, 'cash', null, 'Full');

        $booking->refresh();
        $this->assertSame('paid', $booking->payment_status);
        $this->assertNotNull($booking->paid_at);
        $this->assertEquals(100000, $booking->amount_received);
    }

    public function test_a_payment_never_overpays_the_balance(): void
    {
        $this->actingStaff();
        $booking = Booking::factory()->create(['total_amount' => 100000, 'amount_received' => 90000]);

        // Try to pay 50k against a 10k balance — only 10k should be applied.
        $applied = $booking->recordPayment(50000, 'pos', null, 'Overpay attempt');

        $this->assertSame(10000.0, $applied);
        $booking->refresh();
        $this->assertEquals(100000, $booking->amount_received);
        $this->assertSame('paid', $booking->payment_status);

        $txns = FinancialTransaction::where('reference_id', $booking->id)->get();
        $this->assertCount(1, $txns);
        $this->assertEquals(10000, $txns->first()->amount);
    }

    public function test_a_zero_balance_records_nothing(): void
    {
        $this->actingStaff();
        $booking = Booking::factory()->create(['total_amount' => 100000, 'amount_received' => 100000]);

        $applied = $booking->recordPayment(5000, 'cash', null, 'No balance');

        $this->assertSame(0.0, $applied);
        $this->assertSame(0, FinancialTransaction::where('reference_id', $booking->id)->count());
    }
}
