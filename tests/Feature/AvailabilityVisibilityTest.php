<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Building;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AvailabilityVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->building = Building::factory()->create();
        $unitType = UnitType::factory()->create(['building_id' => $this->building->id]);
        $unit     = Unit::factory()->create(['unit_type_id' => $unitType->id]);

        $this->booking = Booking::factory()->create([
            'building_id'  => $this->building->id,
            'unit_type_id' => $unitType->id,
            'unit_id'      => $unit->id,
            'guest_name'   => 'Ada Lovelace',
            'guest_phone'  => '08011112222',
            'status'       => 'confirmed',
            'check_in'     => now()->toDateString(),
            'check_out'    => now()->addDays(3)->toDateString(),
        ]);
    }

    /** A user scoped to the building with exactly the given permissions. */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $user->givePermissionTo($permissions);
        $user->buildings()->attach($this->building->id);

        return $user;
    }

    private function bookingProp(AssertableInertia $page): array
    {
        return $page->toArray()['props']['buildings'][0]['unit_types'][0]['units'][0]['bookings'][0];
    }

    public function test_a_privileged_user_sees_guest_identity(): void
    {
        $this->actingAs($this->userWith(['manage-availability', 'view-bookings']));

        $this->get(route('manage.availability.index'))
            ->assertInertia(function (AssertableInertia $page) {
                $b = $this->bookingProp($page);
                $this->assertSame('Ada Lovelace', $b['guest_name']);
                $this->assertSame('08011112222', $b['guest_phone']);
                $this->assertNotNull($b['reference']);
            });
    }

    public function test_a_non_privileged_user_sees_occupancy_but_no_guest_pii(): void
    {
        // manage-availability without view-bookings (e.g. QC checking occupancy).
        $this->actingAs($this->userWith(['manage-availability']));

        $this->get(route('manage.availability.index'))
            ->assertInertia(function (AssertableInertia $page) {
                $b = $this->bookingProp($page);
                // Occupancy is visible…
                $this->assertSame('confirmed', $b['status']);
                // …but identity and money are withheld.
                $this->assertNull($b['guest_name']);
                $this->assertNull($b['guest_phone']);
                $this->assertNull($b['reference']);
                $this->assertNull($b['payment_status']);
                $this->assertNull($b['total_amount']);
            });
    }

    public function test_guest_name_does_not_appear_anywhere_in_the_payload_for_non_privileged(): void
    {
        $this->actingAs($this->userWith(['manage-availability']));

        // Belt-and-braces: the string must not leak anywhere in the response.
        $this->get(route('manage.availability.index'))
            ->assertDontSee('Ada Lovelace', false)
            ->assertDontSee('08011112222', false);
    }
}
