<?php

namespace Tests\Feature;

use App\Models\TrafficFine;
use App\Models\Vehicle;
use App\Services\FinesProcessorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinesReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function processor(): FinesProcessorService
    {
        return app(FinesProcessorService::class);
    }

    protected function ticket(array $overrides = []): array
    {
        return array_merge([
            'ticketNumber' => 'TK-001',
            'plateNo' => 'RAG386L',
            'ticketAmount' => 35000.0,
            'lateFee' => 0.0,
            'paidAmount' => 0,
            // The portal reports the amount still OWED here, not a payment.
            'paymentAmount' => 35000,
            'paidAt' => null,
            'issuedAt' => '2026-08-08',
            'payBy' => '2026-09-07',
            'status' => 'PENDING',
            'locationName' => 'Kigali',
            'violations' => [
                ['violationName' => 'Impede the traffic on road', 'fineAmount' => 35000, 'quantity' => 1],
            ],
        ], $overrides);
    }

    /** @test */
    public function it_creates_fines_from_the_portal_payload()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket()],
        ]);

        $this->assertDatabaseHas('traffic_fines', [
            'ticket_number' => 'TK-001',
            'status' => TrafficFine::STATUS_PENDING,
            'plate_number' => 'RAG386L',
            'fineable_id' => $vehicle->id,
        ]);
    }

    /**
     * The core regression: paying one of several fines must settle that fine
     * even though the plate still reports other outstanding tickets.
     *
     * @test
     */
    public function it_settles_a_paid_fine_on_a_partial_response()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [
                $this->ticket(['ticketNumber' => 'TK-001', 'ticketAmount' => 35000.0]),
                $this->ticket(['ticketNumber' => 'TK-002', 'ticketAmount' => 20000.0]),
            ],
        ]);

        $this->assertSame(2, TrafficFine::outstanding()->count());

        // The driver pays TK-001. The portal now only reports TK-002.
        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket(['ticketNumber' => 'TK-002', 'ticketAmount' => 20000.0])],
        ]);

        $settled = TrafficFine::where('ticket_number', 'TK-001')->first();
        $remaining = TrafficFine::where('ticket_number', 'TK-002')->first();

        $this->assertTrue($settled->isSettled(), 'TK-001 should have settled when it dropped off the response');
        $this->assertSame('PAID', $settled->status);
        $this->assertNotNull($settled->paid_at);
        $this->assertEquals(35000.0, (float) $settled->paid_amount);

        $this->assertFalse($remaining->isSettled(), 'TK-002 is still reported and must stay outstanding');
        $this->assertSame(TrafficFine::STATUS_PENDING, $remaining->status);

        $this->assertSame(1, TrafficFine::outstanding()->count());
    }

    /** @test */
    public function it_settles_every_fine_when_the_plate_goes_clear()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [
                $this->ticket(['ticketNumber' => 'TK-001']),
                $this->ticket(['ticketNumber' => 'TK-002']),
            ],
        ]);

        $this->processor()->process('RAG386L', $vehicle, ['status' => 'clear', 'tickets' => []]);

        $this->assertSame(0, TrafficFine::outstanding()->count());
        $this->assertSame(2, TrafficFine::settled()->count());
    }

    /** @test */
    public function it_does_not_reopen_a_settled_fine_when_the_portal_still_lists_it()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket()],
        ]);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'clear',
            'tickets' => [],
        ]);

        $this->assertSame(1, TrafficFine::settled()->count());

        // Portal lags and reports the ticket as outstanding again.
        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket()],
        ]);

        $fine = TrafficFine::first();
        $this->assertTrue($fine->isSettled(), 'A settled fine must never flip back to unpaid');
        $this->assertSame(1, TrafficFine::settled()->count());
        $this->assertSame(0, TrafficFine::outstanding()->count());
    }

    /** @test */
    public function it_settles_when_the_portal_explicitly_reports_a_paid_at_date()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket(['lateFee' => 5000.0, 'paymentAmount' => 40000])],
        ]);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket([
                'lateFee' => 5000.0,
                'paidAt' => '2026-09-10 08:30:00',
                'paidAmount' => 40000,
                'paymentAmount' => 0,
                'status' => 'PAID',
            ])],
        ]);

        $fine = TrafficFine::first();

        $this->assertTrue($fine->isSettled());
        $this->assertNotNull($fine->paid_at);
        // 35,000 principal + 5,000 late fee.
        $this->assertEquals(40000.0, (float) $fine->paid_amount);
    }

    /**
     * The portal's `paymentAmount` is the outstanding balance, not a payment.
     * Reading it as a settlement would mark every returned ticket paid the
     * instant it was fetched.
     *
     * @test
     */
    public function payment_amount_does_not_settle_a_fine()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket([
                'ticketAmount' => 35000.0,
                'lateFee' => 7000.0,
                'paymentAmount' => 42000,
                'paidAmount' => 0,
                'paidAt' => null,
            ])],
        ]);

        $fine = TrafficFine::first();

        $this->assertSame(TrafficFine::STATUS_PENDING, $fine->status);
        $this->assertFalse($fine->isSettled());
        $this->assertSame(1, TrafficFine::outstanding()->count());
        $this->assertEquals(42000.0, $fine->total_due);
    }

    /** @test */
    public function it_settles_when_the_portal_reports_a_paid_amount()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket(['paidAmount' => 35000, 'paymentAmount' => 0])],
        ]);

        $fine = TrafficFine::first();

        $this->assertTrue($fine->isSettled());
        $this->assertEquals(35000.0, (float) $fine->paid_amount);
    }

    /** @test */
    public function reconciliation_is_idempotent_across_repeated_sweeps()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket()],
        ]);
        $this->processor()->process('RAG386L', $vehicle, ['status' => 'clear', 'tickets' => []]);

        $paidAt = TrafficFine::first()->paid_at;

        for ($i = 0; $i < 3; $i++) {
            $this->processor()->process('RAG386L', $vehicle, ['status' => 'clear', 'tickets' => []]);
        }

        $this->assertSame(1, TrafficFine::count(), 'Repeated clear sweeps must not duplicate fines');
        $this->assertEquals($paidAt, TrafficFine::first()->paid_at, 'paid_at must not be rewritten');
    }

    /** @test */
    public function repeated_polls_do_not_duplicate_an_unchanged_ticket()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        for ($i = 0; $i < 5; $i++) {
            $this->processor()->process('RAG386L', $vehicle, [
                'status' => 'fined',
                'tickets' => [$this->ticket()],
            ]);
        }

        $this->assertSame(1, TrafficFine::count());
    }

    /** @test */
    public function a_settled_amount_includes_the_late_fee()
    {
        $vehicle = Vehicle::factory()->create(['plate_number' => 'RAG386L']);

        $this->processor()->process('RAG386L', $vehicle, [
            'status' => 'fined',
            'tickets' => [$this->ticket(['ticketAmount' => 35000.0, 'lateFee' => 7000.0])],
        ]);

        $this->assertEquals(42000.0, TrafficFine::first()->total_due);

        $this->processor()->process('RAG386L', $vehicle, ['status' => 'clear', 'tickets' => []]);

        $fine = TrafficFine::first();
        $this->assertEquals(42000.0, (float) $fine->paid_amount);
        $this->assertEquals(0.0, $fine->total_due, 'A settled fine owes nothing');
    }
}