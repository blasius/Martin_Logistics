<?php

namespace Tests\Feature;

use App\Models\TrafficFine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FineIssuanceDateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a fine that was issued on one date but imported (created) on
     * another, which is the normal case for portal-synced tickets.
     */
    protected function fine(string $issued, string $created, array $overrides = []): TrafficFine
    {
        return TrafficFine::create(array_merge([
            'ticket_number' => 'TK-' . uniqid(),
            'ticket_amount' => 25000.0,
            'late_fee' => 0.0,
            'issued_at' => $issued,
            'pay_by' => Carbon::parse($issued)->addDays(30)->toDateString(),
            'status' => TrafficFine::STATUS_PENDING,
            'plate_number' => 'RAG386L',
            'created_at' => $created,
            'updated_at' => $created,
        ], $overrides));
    }

    /** @test */
    public function issued_between_buckets_by_the_offence_date_not_the_import_date()
    {
        $from = now()->subDays(29)->startOfDay();
        $to = now()->endOfDay();

        // Issued inside the window, imported long before it.
        $this->fine(now()->subDays(10)->toDateString(), now()->subDays(200)->toDateString());

        // Issued outside the window, imported inside it.
        $this->fine(now()->subDays(90)->toDateString(), now()->toDateString());

        $ids = TrafficFine::issuedBetween($from, $to)->pluck('id')->all();

        $this->assertCount(1, $ids, 'Only the ticket issued inside the window should be returned');
    }

    /** @test */
    public function issued_on_selects_a_single_offence_day()
    {
        $target = now()->subDays(5)->toDateString();

        $match = $this->fine($target, now()->toDateString());
        $this->fine(now()->subDays(6)->toDateString(), now()->toDateString());

        $ids = TrafficFine::issuedOn($target)->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
    }

    /** @test */
    public function the_documented_date_column_is_the_issue_date()
    {
        $this->assertSame('issued_at', TrafficFine::ISSUED_DATE_COLUMN);
    }

    /** @test */
    public function listing_orders_by_issue_date_so_a_late_import_does_not_look_recent()
    {
        $older = $this->fine(now()->subDays(40)->toDateString(), now()->toDateString());
        $newer = $this->fine(now()->subDays(2)->toDateString(), now()->subDays(100)->toDateString());

        $ordered = TrafficFine::orderByDesc('issued_at')->pluck('id')->all();

        $this->assertSame([$newer->id, $older->id], $ordered);
    }

    /** @test */
    public function the_analytics_timeseries_places_a_ticket_on_its_offence_date()
    {
        $offenceDay = now()->subDays(3)->toDateString();

        // Issued 3 days ago, imported today.
        $this->fine($offenceDay, now()->toDateString());

        $rows = TrafficFine::selectRaw('DATE(issued_at) as date, COUNT(*) as count')
            ->issuedBetween(now()->subDays(29)->startOfDay(), now()->endOfDay())
            ->groupBy('date')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame($offenceDay, $rows->first()->date);
        $this->assertSame(1, (int) $rows->first()->count);
    }

    /** @test */
    public function tickets_with_no_issue_date_are_excluded_from_the_timeline()
    {
        $this->fine(now()->toDateString(), now()->toDateString());
        $this->fine(now()->toDateString(), now()->toDateString(), ['issued_at' => null]);

        $count = TrafficFine::issuedBetween(now()->subDays(29)->startOfDay(), now()->endOfDay())->count();
        $undated = TrafficFine::whereNull('issued_at')->count();

        $this->assertSame(1, $count);
        $this->assertSame(1, $undated, 'Undated tickets must still be countable so the gap is visible');
    }
}