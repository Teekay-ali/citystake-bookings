<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\FinancialTransaction;
use App\Models\PaymentApproval;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentApprovalTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->building = Building::factory()->create();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['is_staff' => true, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function approval(string $status, ?User $requester = null): PaymentApproval
    {
        return PaymentApproval::create([
            'building_id'    => $this->building->id,
            'type'           => 'vendor_payment',
            'recipient_name' => 'ACME Ltd',
            'amount'         => 75000,
            'requested_by'   => ($requester ?? $this->user('accountant'))->id,
            'status'         => $status,
        ]);
    }

    public function test_an_accountant_submits_a_pending_request(): void
    {
        $accountant = $this->user('accountant');

        $this->actingAs($accountant)->post(route('manage.payment-approvals.store'), [
            'building_id'    => $this->building->id,
            'type'           => 'vendor_payment',
            'recipient_name' => 'ACME Ltd',
            'amount'         => 75000,
        ])->assertRedirect();

        $this->assertDatabaseHas('payment_approvals', [
            'recipient_name' => 'ACME Ltd',
            'status'         => 'pending',
            'requested_by'   => $accountant->id,
        ]);
    }

    public function test_only_the_ceo_can_decide(): void
    {
        $approval = $this->approval('pending');

        // An accountant is not allowed to decide.
        $this->actingAs($this->user('accountant'))
            ->post(route('manage.payment-approvals.decide', $approval), ['decision' => 'approved'])
            ->assertForbidden();

        $this->assertSame('pending', $approval->refresh()->status);
    }

    public function test_ceo_can_approve_or_decline(): void
    {
        $approved = $this->approval('pending');
        $this->actingAs($this->user('ceo'))
            ->post(route('manage.payment-approvals.decide', $approved), ['decision' => 'approved'])
            ->assertRedirect();
        $this->assertSame('approved', $approved->refresh()->status);

        $declined = $this->approval('pending');
        $this->actingAs($this->user('ceo'))
            ->post(route('manage.payment-approvals.decide', $declined), ['decision' => 'declined'])
            ->assertRedirect();
        $this->assertSame('declined', $declined->refresh()->status);
    }

    public function test_a_non_pending_request_cannot_be_decided(): void
    {
        $approval = $this->approval('approved');

        $this->actingAs($this->user('ceo'))
            ->post(route('manage.payment-approvals.decide', $approval), ['decision' => 'declined'])
            ->assertForbidden();

        $this->assertSame('approved', $approval->refresh()->status);
    }

    public function test_marking_paid_books_an_expense(): void
    {
        Storage::fake('public');
        $accountant = $this->user('accountant');
        $approval   = $this->approval('approved', $accountant);

        $this->actingAs($accountant)->post(route('manage.payment-approvals.mark-paid', $approval), [
            'payment_reference' => 'TRF-99',
            'payment_evidence'  => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame('paid', $approval->refresh()->status);

        $txn = FinancialTransaction::where('reference_type', PaymentApproval::class)
            ->where('reference_id', $approval->id)->first();
        $this->assertNotNull($txn);
        $this->assertSame('expense', $txn->type);
        $this->assertEquals(75000, $txn->amount);
    }

    public function test_a_request_cannot_be_paid_before_approval(): void
    {
        $accountant = $this->user('accountant');
        $approval   = $this->approval('pending', $accountant);

        $this->actingAs($accountant)->post(route('manage.payment-approvals.mark-paid', $approval), [
            'payment_evidence' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
        ])->assertForbidden();

        $this->assertSame(0, FinancialTransaction::where('reference_id', $approval->id)->count());
    }

    public function test_only_the_requester_can_mark_paid(): void
    {
        $requester = $this->user('accountant');
        $approval  = $this->approval('approved', $requester);

        // A different accountant cannot pay someone else's request.
        $this->actingAs($this->user('accountant'))
            ->post(route('manage.payment-approvals.mark-paid', $approval), [
                'payment_evidence' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
            ])->assertForbidden();

        $this->assertSame('approved', $approval->refresh()->status);
    }
}
