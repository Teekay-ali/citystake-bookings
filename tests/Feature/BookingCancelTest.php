<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Building;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCancelTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->building = Building::factory()->create();
    }

    private function manager(): User
    {
        $user = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $user->assignRole('manager'); // holds manage-bookings
        $user->buildings()->attach($this->building->id);

        return $user;
    }

    private function booking(string $status = 'confirmed'): Booking
    {
        return Booking::factory()->create([
            'building_id' => $this->building->id,
            'status'      => $status,
        ]);
    }

    public function test_a_manager_can_cancel_a_booking(): void
    {
        $booking = $this->booking('confirmed');

        $this->actingAs($this->manager())
            ->post(route('manage.bookings.cancel', $booking), ['reason' => 'Guest requested cancellation'])
            ->assertRedirect(route('manage.bookings.show', $booking->booking_reference));

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertNotNull($booking->cancelled_at);
        $this->assertSame('Guest requested cancellation', $booking->cancellation_reason);
    }

    public function test_cancelling_writes_an_audit_entry_for_the_booking(): void
    {
        $booking = $this->booking('confirmed');

        $this->actingAs($this->manager())
            ->post(route('manage.bookings.cancel', $booking), ['reason' => 'Double booking']);

        // The audit trail references the exact booking (so it can be traced/viewed).
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'booking.cancelled',
            'model_type' => Booking::class,
            'model_id'   => $booking->id,
        ]);

        $audit = \App\Models\AuditLog::where('model_id', $booking->id)
            ->where('action', 'booking.cancelled')->latest('id')->first();
        $this->assertSame('Double booking', $audit->new_values['reason']);
        $this->assertSame($booking->booking_reference, $audit->new_values['reference']);
    }

    public function test_a_reason_is_optional(): void
    {
        $booking = $this->booking('confirmed');

        $this->actingAs($this->manager())
            ->post(route('manage.bookings.cancel', $booking), [])
            ->assertRedirect();

        $this->assertSame('cancelled', $booking->refresh()->status);
        $this->assertNull($booking->cancellation_reason);
    }

    public function test_a_completed_booking_cannot_be_cancelled(): void
    {
        $booking = $this->booking('completed');

        $this->actingAs($this->manager())
            ->post(route('manage.bookings.cancel', $booking), [])
            ->assertSessionHas('error');

        $this->assertSame('completed', $booking->refresh()->status);
    }

    public function test_an_already_cancelled_booking_is_rejected(): void
    {
        $booking = $this->booking('cancelled');

        $this->actingAs($this->manager())
            ->post(route('manage.bookings.cancel', $booking), [])
            ->assertSessionHas('error');
    }

    public function test_a_manager_from_another_building_is_forbidden(): void
    {
        $booking = $this->booking('confirmed');

        $other = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $other->assignRole('manager');
        $other->buildings()->attach(Building::factory()->create()->id);

        $this->actingAs($other)
            ->post(route('manage.bookings.cancel', $booking), [])
            ->assertForbidden();

        $this->assertSame('confirmed', $booking->refresh()->status);
    }

    public function test_a_user_without_manage_bookings_is_forbidden(): void
    {
        $booking = $this->booking('confirmed');

        // Quality control holds no manage-bookings.
        $qc = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $qc->assignRole('quality-control');
        $qc->buildings()->attach($this->building->id);

        $this->actingAs($qc)
            ->post(route('manage.bookings.cancel', $booking), [])
            ->assertForbidden();

        $this->assertSame('confirmed', $booking->refresh()->status);
    }
}
