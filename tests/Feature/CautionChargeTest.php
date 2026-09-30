<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CautionFeeCharge;
use App\Models\FinancialTransaction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CautionChargeTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->building = \App\Models\Building::factory()->create();

        // Receptionist holds manage-bookings; scope them to the test building.
        $staff = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $staff->assignRole('receptionist');
        $staff->buildings()->attach($this->building->id);
        $this->actingAs($staff);
    }

    private function booking(float $cautionFee = 100000): Booking
    {
        return Booking::factory()->create([
            'building_id'  => $this->building->id,
            'caution_fee'  => $cautionFee,
        ]);
    }

    private function charge(Booking $booking, array $data)
    {
        return $this->post(route('manage.bookings.caution-charges.store', $booking), $data);
    }

    public function test_a_charge_books_income_and_reduces_the_available_balance(): void
    {
        $booking = $this->booking(100000);

        $this->charge($booking, ['category' => 'damage', 'description' => 'Broken lamp', 'amount' => 30000])
            ->assertRedirect();

        $charge = CautionFeeCharge::where('booking_id', $booking->id)->first();
        $this->assertNotNull($charge);
        $this->assertEquals(30000, $charge->amount);
        $this->assertNotNull($charge->financial_transaction_id);

        $txn = FinancialTransaction::find($charge->financial_transaction_id);
        $this->assertSame('income', $txn->type);
        $this->assertEquals(30000, $txn->amount);
        $this->assertSame('caution_fee', $txn->payment_method);

        $this->assertEquals(70000, $booking->fresh()->caution_available);
    }

    public function test_a_charge_cannot_exceed_the_available_balance(): void
    {
        $booking = $this->booking(50000);

        $this->charge($booking, ['category' => 'damage', 'description' => 'Too much', 'amount' => 60000])
            ->assertSessionHas('error');

        $this->assertSame(0, CautionFeeCharge::where('booking_id', $booking->id)->count());
        $this->assertSame(0, FinancialTransaction::where('reference_id', $booking->id)->count());
    }

    public function test_a_booking_without_a_caution_fee_cannot_be_charged(): void
    {
        $booking = $this->booking(0);

        $this->charge($booking, ['category' => 'food', 'description' => 'Snacks', 'amount' => 1000])
            ->assertSessionHas('error');

        $this->assertSame(0, CautionFeeCharge::where('booking_id', $booking->id)->count());
    }

    public function test_voiding_a_charge_reverses_the_income_and_restores_the_balance(): void
    {
        $booking = $this->booking(100000);
        $this->charge($booking, ['category' => 'damage', 'description' => 'Broken lamp', 'amount' => 30000]);
        $charge = CautionFeeCharge::where('booking_id', $booking->id)->firstOrFail();
        $txnId  = $charge->financial_transaction_id;

        $this->post(route('manage.bookings.caution-charges.void', [$booking, $charge]), ['reason' => 'Charged in error'])
            ->assertRedirect();

        $charge->refresh();
        $this->assertNotNull($charge->voided_at);
        $this->assertNull($charge->financial_transaction_id);
        // The recognised income is reversed (transaction deleted).
        $this->assertNull(FinancialTransaction::find($txnId));
        // Available caution is restored in full.
        $this->assertEquals(100000, $booking->fresh()->caution_available);
    }

    public function test_an_already_voided_charge_cannot_be_voided_again(): void
    {
        $booking = $this->booking(100000);
        $this->charge($booking, ['category' => 'other', 'description' => 'X', 'amount' => 10000]);
        $charge = CautionFeeCharge::where('booking_id', $booking->id)->firstOrFail();

        $this->post(route('manage.bookings.caution-charges.void', [$booking, $charge]), ['reason' => 'first']);
        $this->post(route('manage.bookings.caution-charges.void', [$booking, $charge]), ['reason' => 'again'])
            ->assertSessionHas('error');

        $this->assertSame(1, CautionFeeCharge::where('booking_id', $booking->id)->whereNotNull('voided_at')->count());
    }

    public function test_a_charge_cannot_be_voided_through_another_booking(): void
    {
        $bookingA = $this->booking(100000);
        $this->charge($bookingA, ['category' => 'damage', 'description' => 'A', 'amount' => 20000]);
        $charge = CautionFeeCharge::where('booking_id', $bookingA->id)->firstOrFail();

        $bookingB = $this->booking(100000); // same building, so scope passes → 404 on mismatch

        $this->post(route('manage.bookings.caution-charges.void', [$bookingB, $charge]), ['reason' => 'wrong booking'])
            ->assertNotFound();

        $this->assertNull($charge->fresh()->voided_at);
    }
}
