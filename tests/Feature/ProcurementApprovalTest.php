<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\ProcurementRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->building = Building::factory()->create();
    }

    /** A user with $role, scoped to the test building (ceo/super-admin are global). */
    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $user->assignRole($role);
        if (! $user->hasGlobalAccess()) {
            $user->buildings()->attach($this->building->id);
        }

        return $user;
    }

    private function request(string $status = 'pending'): ProcurementRequest
    {
        return ProcurementRequest::create([
            'reference'     => 'PR-' . fake()->unique()->numerify('########'),
            'building_id'   => $this->building->id,
            'submitted_by'  => User::factory()->create()->id,
            'title'         => 'Cleaning supplies',
            'justification' => 'Monthly restock',
            'total_amount'  => 50000,
            'status'        => $status,
        ]);
    }

    private function approve(User $user, ProcurementRequest $pr): void
    {
        $this->actingAs($user)
            ->post(route('manage.procurement.approve', $pr), ['action' => 'approve'])
            ->assertRedirect();
    }

    public function test_full_chain_advances_through_each_role(): void
    {
        $pr = $this->request();

        $this->approve($this->userWithRole('head-of-procurement'), $pr);
        $this->assertSame('officer_approved', $pr->refresh()->status);

        $this->approve($this->userWithRole('accountant'), $pr);
        $this->assertSame('accountant_approved', $pr->refresh()->status);

        $this->approve($this->userWithRole('ceo'), $pr);
        $this->assertSame('ceo_approved', $pr->refresh()->status);

        // Purchase is the officer's purchase-procurement capability.
        $this->approve($this->userWithRole('head-of-procurement'), $pr);
        $this->assertSame('purchased', $pr->refresh()->status);
    }

    public function test_a_role_cannot_act_out_of_turn(): void
    {
        $pr = $this->request(); // pending — only the officer may act first

        // The accountant has no officer permission and it isn't their stage yet.
        $this->actingAs($this->userWithRole('accountant'))
            ->post(route('manage.procurement.approve', $pr), ['action' => 'approve']);

        $this->assertSame('pending', $pr->refresh()->status);
    }

    public function test_rejection_ends_the_chain(): void
    {
        $pr = $this->request();

        $this->actingAs($this->userWithRole('head-of-procurement'))
            ->post(route('manage.procurement.approve', $pr), ['action' => 'reject', 'notes' => 'Not needed'])
            ->assertRedirect();

        $this->assertSame('rejected', $pr->refresh()->status);
    }

    public function test_a_user_from_another_building_is_forbidden(): void
    {
        $pr    = $this->request();
        $other = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $other->assignRole('head-of-procurement');
        $other->buildings()->attach(Building::factory()->create()->id);

        $this->actingAs($other)
            ->post(route('manage.procurement.approve', $pr), ['action' => 'approve'])
            ->assertForbidden();

        $this->assertSame('pending', $pr->refresh()->status);
    }
}
