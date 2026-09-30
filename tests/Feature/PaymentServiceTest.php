<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentService::class);
        $this->actingAs(User::factory()->create(['is_staff' => true]));
    }

    public function test_settle_installment_books_income_and_links_the_transaction(): void
    {
        $booking     = Booking::factory()->create(['total_amount' => 120000]);
        $installment = BookingInstallment::create([
            'booking_id'  => $booking->id,
            'week_number' => 1,
            'due_date'    => now()->toDateString(),
            'amount'      => 40000,
        ]);

        $this->service->settleInstallment($installment, 'pos', 'REF-W1');

        $installment->refresh();
        $this->assertNotNull($installment->paid_at);
        $this->assertNotNull($installment->financial_transaction_id);

        $txn = FinancialTransaction::find($installment->financial_transaction_id);
        $this->assertSame('income', $txn->type);
        $this->assertEquals(40000, $txn->amount);
    }

    public function test_settle_installment_is_idempotent(): void
    {
        $booking     = Booking::factory()->create();
        $installment = BookingInstallment::create([
            'booking_id'  => $booking->id,
            'week_number' => 1,
            'due_date'    => now()->toDateString(),
            'amount'      => 40000,
            'paid_at'     => now(),
        ]);

        $this->service->settleInstallment($installment, 'pos', 'REF');

        // Already paid — no second transaction is written.
        $this->assertSame(0, FinancialTransaction::where('reference_id', $booking->id)->count());
    }

    public function test_settle_late_checkout_marks_settled_and_books_the_fee(): void
    {
        $booking = Booking::factory()->create([
            'late_checkout_fee'    => 15000,
            'late_checkout_status' => 'approved',
        ]);

        $this->service->settleLateCheckout($booking);

        $booking->refresh();
        $this->assertSame('settled', $booking->late_checkout_status);
        $this->assertNotNull($booking->late_checkout_settled_at);

        $txn = FinancialTransaction::where('reference_id', $booking->id)
            ->where('category', 'late_checkout')->first();
        $this->assertNotNull($txn);
        $this->assertEquals(15000, $txn->amount);
        $this->assertSame('income', $txn->type);
    }
}
