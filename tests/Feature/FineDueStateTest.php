<?php

namespace Tests\Feature;

use App\Models\TrafficFine;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FineDueStateTest extends TestCase
{
    use RefreshDatabase;

    protected function fine(array $overrides = []): TrafficFine
    {
        return TrafficFine::create(array_merge([
            'ticket_number' => 'TK-' . uniqid(),
            'ticket_amount' => 35000.0,
            'late_fee' => 0.0,
            'paid_amount' => 0,
            'issued_at' => now()->subDays(20)->toDateString(),
            'pay_by' => now()->addDays(10)->toDateString(),
            'status' => TrafficFine::STATUS_PENDING,
            'plate_number' => 'RAG386L',
        ], $overrides));
    }

    /** @test */
    public function overdue_is_driven_by_pay_by_not_the_age_of_the_ticket()
    {
        // A ticket issued 60 days ago but not yet due must NOT be overdue.
        $notDue = $this->fine([
            'issued_at' => now()->subDays(60)->toDateString(),
            'pay_by' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertFalse($notDue->isOverdue());
        $this->assertSame(TrafficFine::DUE_STATE_DUE, $notDue->dueState());

        // A ticket issued yesterday but already past its due date IS overdue.
        $overdue = $this->fine([
            'issued_at' => now()->subDay()->toDateString(),
            'pay_by' => now()->subDays(2)->toDateString(),
        ]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertSame(TrafficFine::DUE_STATE_OVERDUE, $overdue->dueState());
        $this->assertSame(2, $overdue->daysOverdue());
    }

    /** @test */
    public function it_reports_a_due_soon_band_before_the_due_date()
    {
        $fine = $this->fine(['pay_by' => now()->addDays(TrafficFine::DUE_SOON_DAYS)->toDateString()]);

        $this->assertSame(TrafficFine::DUE_STATE_DUE_SOON, $fine->dueState());
        $this->assertFalse($fine->isOverdue());
    }

    /** @test */
    public function a_due_date_of_today_is_due_soon_and_not_overdue()
    {
        $fine = $this->fine(['pay_by' => now()->toDateString()]);

        $this->assertSame(TrafficFine::DUE_STATE_DUE_SOON, $fine->dueState());
        $this->assertFalse($fine->isOverdue());
        $this->assertSame(0, $fine->daysUntilPayBy());
    }

    /** @test */
    public function settled_fines_are_never_overdue_or_due_soon()
    {
        $fine = $this->fine([
            'pay_by' => now()->subDays(30)->toDateString(),
            'status' => TrafficFine::STATUS_PAID,
        ]);

        $this->assertSame(TrafficFine::DUE_STATE_SETTLED, $fine->dueState());
        $this->assertFalse($fine->isOverdue());
        $this->assertEquals(0.0, $fine->total_due);
    }

    /** @test */
    public function a_fine_without_a_pay_by_date_reports_unknown_rather_than_guessing()
    {
        $fine = $this->fine(['pay_by' => null]);

        $this->assertSame(TrafficFine::DUE_STATE_UNKNOWN, $fine->dueState());
        $this->assertNull($fine->daysUntilPayBy());
        $this->assertSame(0, $fine->daysOverdue());
    }

    /** @test */
    public function total_due_combines_principal_and_late_fee()
    {
        $fine = $this->fine(['ticket_amount' => 35000.0, 'late_fee' => 7000.0]);

        $this->assertEquals(35000.0, $fine->principal);
        $this->assertEquals(42000.0, $fine->total_due);
    }

    /** @test */
    public function outstanding_scope_excludes_every_settled_status()
    {
        $this->fine(['status' => TrafficFine::STATUS_PENDING]);
        $this->fine(['status' => 'UNPAID']);
        $this->fine(['status' => TrafficFine::STATUS_PAID]);
        $this->fine(['status' => TrafficFine::STATUS_CANCELLED]);
        $this->fine(['status' => TrafficFine::STATUS_DISPUTED]);

        $this->assertSame(2, TrafficFine::outstanding()->count());
        $this->assertSame(3, TrafficFine::settled()->count());
    }

    /** @test */
    public function overdue_scope_only_returns_unpaid_fines_past_their_pay_by()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $pendingOverdue = $this->fine([
            'pay_by' => now()->subDay()->toDateString(),
            'status' => TrafficFine::STATUS_PENDING,
        ]);

        $settledOverdue = $this->fine([
            'pay_by' => now()->subDays(40)->toDateString(),
            'status' => TrafficFine::STATUS_PAID,
        ]);

        $pendingFuture = $this->fine([
            'pay_by' => now()->addDays(20)->toDateString(),
            'status' => TrafficFine::STATUS_PENDING,
        ]);

        $pendingOverdue->fineable()->associate($vehicle);
        $pendingOverdue->save();
        $settledOverdue->fineable()->associate($vehicle);
        $settledOverdue->save();
        $pendingFuture->fineable()->associate($vehicle);
        $pendingFuture->save();

        $this->assertSame([$pendingOverdue->id], TrafficFine::overdue()->pluck('id')->all());
    }

    /** @test */
    public function penalized_scope_targets_outstanding_fines_with_a_late_fee()
    {
        $this->fine(['late_fee' => 5000.0, 'status' => TrafficFine::STATUS_PENDING]);
        $this->fine(['late_fee' => 0.0, 'status' => TrafficFine::STATUS_PENDING]);
        $this->fine(['late_fee' => 5000.0, 'status' => TrafficFine::STATUS_PAID]);

        $this->assertSame(1, TrafficFine::penalized()->count());
    }

    /** @test */
    public function settling_a_fine_records_the_full_amount_and_is_idempotent()
    {
        $fine = $this->fine(['ticket_amount' => 35000.0, 'late_fee' => 7000.0]);

        $this->assertTrue($fine->settleAsPaid());
        $this->assertSame('PAID', $fine->status);
        $this->assertEquals(42000.0, (float) $fine->paid_amount);
        $this->assertNotNull($fine->paid_at);

        $paidAt = $fine->paid_at;

        $this->assertFalse($fine->settleAsPaid(), 'Settling an already-settled fine is a no-op');
        $this->assertEquals($paidAt, $fine->fresh()->paid_at);
    }
}