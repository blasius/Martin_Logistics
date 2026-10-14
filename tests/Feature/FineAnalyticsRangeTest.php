<?php

namespace Tests\Feature;

use App\Http\Controllers\API\FinesAnalyticsController;
use App\Models\TrafficFine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FineAnalyticsRangeTest extends TestCase
{
    use RefreshDatabase;

    protected function fine(string $issuedAt, array $overrides = []): TrafficFine
    {
        return TrafficFine::create(array_merge([
            'ticket_number' => 'TK-' . uniqid(),
            'ticket_amount' => 25000.0,
            'late_fee' => 0.0,
            'issued_at' => $issuedAt,
            'pay_by' => Carbon::parse($issuedAt)->addDays(30)->toDateString(),
            'status' => TrafficFine::STATUS_PENDING,
            'plate_number' => 'RAG386L',
        ], $overrides));
    }

    /** @test */
    public function it_exposes_the_expected_preset_ranges()
    {
        $this->assertArrayHasKey('7', FinesAnalyticsController::NAMED_RANGES);
        $this->assertArrayHasKey('30', FinesAnalyticsController::NAMED_RANGES);
        $this->assertArrayHasKey('90', FinesAnalyticsController::NAMED_RANGES);

        $this->assertArrayHasKey('12m', FinesAnalyticsController::NAMED_RANGES);
        $this->assertSame('month', FinesAnalyticsController::NAMED_RANGES['12m']['unit']);
        $this->assertSame(12, FinesAnalyticsController::NAMED_RANGES['12m']['value']);
    }

    /** @test */
    public function a_twelve_month_range_covers_the_last_twelve_calendar_months()
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14'));

        $this->fine('2025-10-15');   // first day of the 12-month window
        $this->fine('2025-10-14');   // one day before it
        $this->fine('2026-05-01');   // comfortably inside

        $series = $this->series('12m');

        $this->assertSame('month', $series['bucket']);

        $counts = collect($series['timeseries'])->keyBy('date');
        $this->assertSame(2, $counts['2025-10']['count'] + $counts['2026-05']['count']);
        $this->assertSame(1, $counts['2026-05']['count']);
        $this->assertSame(1, $counts['2025-10']['count'], 'Only the boundary day should be in range');
        $this->assertSame(2, $series['summary']['total_count']);

        Carbon::setTestNow();
    }

    /** @test */
    public function long_ranges_bucket_by_month_and_short_ranges_by_day()
    {
        $this->assertSame('month', $this->series('12m')['bucket']);
        $this->assertSame('day', $this->series('90')['bucket']);
        $this->assertSame('day', $this->series('7')['bucket']);
    }

    /** @test */
    public function a_custom_window_of_more_than_92_days_buckets_by_month()
    {
        $this->assertSame('month', $this->series('custom', [
            'from' => '2026-01-01', 'to' => '2026-10-14',
        ])['bucket']);

        $this->assertSame('day', $this->series('custom', [
            'from' => '2026-10-01', 'to' => '2026-10-14',
        ])['bucket']);
    }

    /** @test */
    public function a_custom_window_only_counts_tickets_issued_inside_it()
    {
        $this->fine('2026-03-15');
        $this->fine('2026-07-20');
        $this->fine('2026-11-01');

        $result = $this->series('custom', ['from' => '2026-01-01', 'to' => '2026-10-14']);

        $this->assertSame(2, $result['summary']['total_count']);
        $this->assertSame('2026-01-01', $result['summary']['from']);
        $this->assertSame('2026-10-14', $result['summary']['to']);
    }

    /** @test */
    public function a_custom_window_with_start_after_end_is_rejected()
    {
        $result = $this->series('custom', ['from' => '2026-10-14', 'to' => '2026-01-01']);

        $this->assertSame(422, $result['status']);
    }

    /** @test */
    public function an_excessive_custom_window_is_rejected()
    {
        $result = $this->series('custom', ['from' => '1980-01-01', 'to' => '2026-10-14']);

        $this->assertSame(422, $result['status']);
    }

    /** @test */
    public function an_unknown_range_falls_back_to_thirty_days()
    {
        $result = $this->series('nonsense');

        $this->assertSame(200, $result['status']);
        $this->assertSame('30', $result['summary']['range']);
    }

    /** @test */
    public function the_monthly_series_is_zero_filled_across_every_month_in_the_window()
    {
        $this->fine('2026-04-10');

        $series = collect($this->series('custom', ['from' => '2026-01-01', 'to' => '2026-06-30'])['timeseries'])
            ->keyBy('date');

        $this->assertCount(6, $series, 'Jan through Jun must all be present');
        $this->assertSame(1, $series['2026-04']['count']);

        foreach (['2026-01', '2026-02', '2026-03', '2026-05', '2026-06'] as $empty) {
            $this->assertSame(0, $series[$empty]['count'], "{$empty} should be zero filled");
        }
    }

    /** @test */
    public function the_daily_series_is_zero_filled_across_every_day_in_the_window()
    {
        $this->fine('2026-06-02');

        $series = $this->series('custom', ['from' => '2026-06-01', 'to' => '2026-06-05'])['timeseries'];

        $this->assertCount(5, $series);
        // Only the 2nd carries a ticket; the rest are zero filled.
        $this->assertSame([0, 1, 0, 0, 0], array_column($series, 'count'));
        $this->assertSame(
            ['2026-06-01', '2026-06-02', '2026-06-03', '2026-06-04', '2026-06-05'],
            array_column($series, 'date')
        );
    }

    /** @test */
    public function each_point_carries_a_display_label()
    {
        // Longer than the monthly threshold, so points are months.
        $series = $this->series('custom', ['from' => '2026-01-01', 'to' => '2026-06-30'])['timeseries'];

        $this->assertSame('Jan 2026', $series[0]['label']);
        $this->assertSame('Jun 2026', $series[5]['label']);
        $this->assertSame('2026-01', $series[0]['date'], 'The raw key stays machine-readable');
    }

    /**
     * Drive the analytics endpoint and return the decoded body plus status.
     */
    protected function series(string $range, array $extra = []): array
    {
        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->getJson('/api/portal/fines/analytics?' . http_build_query(array_merge(['range' => $range], $extra)));

        return [
            'status' => $response->getStatusCode(),
            'summary' => $response->json('summary') ?? [],
            'timeseries' => $response->json('timeseries') ?? [],
            'bucket' => $response->json('summary.bucket'),
        ];
    }
}