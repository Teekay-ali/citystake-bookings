<?php

namespace Tests\Feature;

use App\Models\BlockedDate;
use App\Models\Unit;
use App\Models\UnitTurnover;
use App\Models\User;
use App\Services\UnitTurnoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class UnitTurnoverServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnitTurnoverService $service;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UnitTurnoverService::class);
        $this->staff   = User::factory()->create(['is_staff' => true]);
    }

    private function unit(): Unit
    {
        return Unit::factory()->create()->load('unitType');
    }

    /** Build an (unsaved) turnover in a given status for pure resolver tests. */
    private function turnover(string $status, array $extra = []): UnitTurnover
    {
        return new UnitTurnover(array_merge(['status' => $status], $extra));
    }

    // ── State machine transitions ───────────────────────────────

    public function test_request_cleaning_opens_a_turnover(): void
    {
        $unit = $this->unit();

        $turnover = $this->service->requestCleaning($unit, null, $this->staff);

        $this->assertSame('cleaning_in_progress', $turnover->status);
        $this->assertSame($this->staff->id, $turnover->cleaning_requested_by);
        $this->assertNotNull($turnover->cleaning_requested_at);
        $this->assertEquals($turnover->id, $this->service->activeFor($unit)->id);
    }

    public function test_request_cleaning_rejects_a_second_active_turnover(): void
    {
        $unit = $this->unit();
        $this->service->requestCleaning($unit, null, $this->staff);

        $this->expectException(RuntimeException::class);
        $this->service->requestCleaning($unit, null, $this->staff);
    }

    public function test_mark_cleaned_advances_to_ready_for_qa(): void
    {
        $unit     = $this->unit();
        $turnover = $this->service->requestCleaning($unit, null, $this->staff);

        $this->service->markCleaned($turnover, $this->staff);

        $this->assertSame('cleaning_completed', $turnover->fresh()->status);
        $this->assertSame($this->staff->id, $turnover->fresh()->cleaning_completed_by);
    }

    public function test_mark_cleaned_rejects_a_turnover_that_is_not_in_cleaning(): void
    {
        $unit     = $this->unit();
        $turnover = UnitTurnover::create([
            'unit_id' => $unit->id, 'building_id' => $unit->unitType->building_id, 'status' => 'ready',
        ]);

        $this->expectException(RuntimeException::class);
        $this->service->markCleaned($turnover, $this->staff);
    }

    public function test_complete_qa_marks_the_unit_ready(): void
    {
        $unit     = $this->unit();
        $turnover = UnitTurnover::create([
            'unit_id' => $unit->id, 'building_id' => $unit->unitType->building_id, 'status' => 'qa_in_progress',
        ]);

        $this->service->completeQa($turnover);

        $fresh = $turnover->fresh();
        $this->assertSame('ready', $fresh->status);
        $this->assertNotNull($fresh->ready_at);
        $this->assertNotNull($fresh->qa_completed_at);
    }

    public function test_complete_qa_rejects_a_turnover_that_is_not_in_qa(): void
    {
        $unit     = $this->unit();
        $turnover = $this->service->requestCleaning($unit, null, $this->staff); // cleaning_in_progress

        $this->expectException(RuntimeException::class);
        $this->service->completeQa($turnover);
    }

    public function test_block_unit_creates_a_blocked_date_and_marks_the_turnover_blocked(): void
    {
        $unit     = $this->unit();
        $turnover = $this->service->requestCleaning($unit, null, $this->staff);

        $blocked = $this->service->blockUnit($unit, now()->toDateString(), now()->addDays(2)->toDateString(), 'Broken AC', $this->staff);

        $this->assertInstanceOf(BlockedDate::class, $blocked);
        $this->assertDatabaseHas('blocked_dates', ['id' => $blocked->id, 'unit_id' => $unit->id]);
        $this->assertSame('blocked', $turnover->fresh()->status);
        $this->assertSame($blocked->id, $turnover->fresh()->blocked_date_id);
    }

    public function test_cancel_turnover_discards_an_active_turnover(): void
    {
        $unit     = $this->unit();
        $turnover = $this->service->requestCleaning($unit, null, $this->staff);

        $this->service->cancelTurnover($turnover);

        $this->assertSame('cancelled', $turnover->fresh()->status);
        $this->assertNull($this->service->activeFor($unit));
    }

    public function test_return_to_service_clears_blocks_and_cancels_the_blocked_turnover(): void
    {
        $unit = $this->unit();
        $this->service->requestCleaning($unit, null, $this->staff);
        $this->service->blockUnit($unit, now()->toDateString(), now()->addDays(2)->toDateString(), 'Repair', $this->staff);

        $this->service->returnToService($unit);

        $this->assertDatabaseMissing('blocked_dates', ['unit_id' => $unit->id]);
        $this->assertSame(0, UnitTurnover::where('unit_id', $unit->id)->where('status', 'blocked')->count());
    }

    // ── readinessState resolver (single source of truth) ────────

    public function test_readiness_blocked_and_occupied_take_priority(): void
    {
        $this->assertSame('blocked', $this->service->readinessState(true, true, null, now(), true));
        $this->assertSame('occupied', $this->service->readinessState(true, false, null, now(), true));
    }

    public function test_readiness_departed_unit_needs_cleaning(): void
    {
        $state = $this->service->readinessState(false, false, null, now()->subDay(), true);
        $this->assertSame('needs_cleaning', $state);
    }

    public function test_readiness_reflects_active_turnover_status(): void
    {
        $this->assertSame('cleaning', $this->service->readinessState(false, false, $this->turnover('cleaning_in_progress'), now(), true));
        $this->assertSame('ready_for_qa', $this->service->readinessState(false, false, $this->turnover('cleaning_completed'), now(), true));
        $this->assertSame('qa_in_progress', $this->service->readinessState(false, false, $this->turnover('qa_in_progress'), now(), true));
    }

    public function test_readiness_ready_turnover_after_departure_is_not_dirty_again(): void
    {
        // Cleaned-and-passed after the guest left → ready, not needs_cleaning.
        $turnover = $this->turnover('ready', ['ready_at' => now()]);
        $state = $this->service->readinessState(false, false, $turnover, now()->subHour(), true);
        $this->assertSame('ready', $state);
    }

    public function test_readiness_offline_and_pending_fallbacks(): void
    {
        $this->assertSame('offline', $this->service->readinessState(false, false, null, null, false));
        $this->assertSame('pending', $this->service->readinessState(false, false, null, null, true));
    }
}
