<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\MaintenanceReport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->building = Building::factory()->create();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $user->assignRole($role);
        if (! $user->hasGlobalAccess()) {
            $user->buildings()->attach($this->building->id);
        }

        return $user;
    }

    private function report(string $status = 'pending'): MaintenanceReport
    {
        return MaintenanceReport::create([
            'building_id'  => $this->building->id,
            'submitted_by' => User::factory()->create()->id,
            'title'        => 'Leaking tap',
            'issue_type'   => 'plumbing',
            'description'  => 'Kitchen tap drips',
            'status'       => $status,
        ]);
    }

    private function submit(User $user, MaintenanceReport $m, array $data)
    {
        return $this->actingAs($user)->post(route('manage.maintenance.approve', $m), $data);
    }

    public function test_full_chain_advances_through_each_role(): void
    {
        $m = $this->report();

        $this->submit($this->userWithRole('manager'), $m, ['action' => 'approve'])->assertRedirect();
        $this->assertSame('manager_approved', $m->refresh()->status);

        // The accountant step sets the actual cost.
        $this->submit($this->userWithRole('accountant'), $m, ['action' => 'approve', 'actual_cost' => 25000])->assertRedirect();
        $m->refresh();
        $this->assertSame('accountant_approved', $m->status);
        $this->assertEquals(25000, $m->actual_cost);

        $this->submit($this->userWithRole('ceo'), $m, ['action' => 'approve'])->assertRedirect();
        $this->assertSame('ceo_approved', $m->refresh()->status);

        // Payment closes it out (accountant holds pay-maintenance).
        $this->submit($this->userWithRole('accountant'), $m, ['action' => 'approve'])->assertRedirect();
        $this->assertSame('completed', $m->refresh()->status);
    }

    public function test_the_accountant_step_requires_an_actual_cost(): void
    {
        $m = $this->report('manager_approved');

        $this->submit($this->userWithRole('accountant'), $m, ['action' => 'approve'])
            ->assertSessionHasErrors('actual_cost');

        $this->assertSame('manager_approved', $m->refresh()->status);
    }

    public function test_a_role_cannot_act_out_of_turn(): void
    {
        $m = $this->report(); // pending — only the manager may act first

        $this->submit($this->userWithRole('accountant'), $m, ['action' => 'approve', 'actual_cost' => 1000]);

        $this->assertSame('pending', $m->refresh()->status);
    }

    public function test_rejection_ends_the_chain(): void
    {
        $m = $this->report();

        $this->submit($this->userWithRole('manager'), $m, ['action' => 'reject', 'notes' => 'Duplicate'])
            ->assertRedirect();

        $this->assertSame('rejected', $m->refresh()->status);
    }
}
