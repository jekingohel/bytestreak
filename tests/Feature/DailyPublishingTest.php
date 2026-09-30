<?php

namespace Tests\Feature;

use App\Enums\ChallengeStatus;
use App\Services\DailyChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_challenge_scheduled_for_today_goes_live(): void
    {
        $this->travelToDay(self::TODAY);
        $scheduled = $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => '2026-09-30']);
        $queued = $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => null]);

        $today = app(DailyChallengeService::class)->today();

        $this->assertTrue($today->is($scheduled));
        $this->assertSame(ChallengeStatus::Published, $scheduled->refresh()->status);
        $this->assertSame(ChallengeStatus::Scheduled, $queued->refresh()->status);
    }

    public function test_an_empty_weekday_is_filled_from_the_front_of_the_queue(): void
    {
        $this->travelToDay(self::TODAY);
        $first = $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => null]);
        $second = $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => null]);

        $this->artisan('challenges:publish-daily')->assertSuccessful();

        $first->refresh();
        $this->assertSame(ChallengeStatus::Published, $first->status);
        $this->assertSame('2026-09-30', $first->publish_date->toDateString());
        $this->assertNull($second->refresh()->publish_date);
    }

    public function test_weekends_are_rest_days_unless_something_is_scheduled(): void
    {
        $this->travelToDay('2026-10-03 10:00:00'); // Saturday
        $queued = $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => null]);

        $this->assertNull(app(DailyChallengeService::class)->today());
        $this->assertSame(ChallengeStatus::Scheduled, $queued->refresh()->status);

        $special = $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => '2026-10-03']);
        app(DailyChallengeService::class)->forget();

        $this->assertTrue(app(DailyChallengeService::class)->today()->is($special));
    }

    public function test_a_scheduled_day_that_was_skipped_becomes_a_bonus_challenge(): void
    {
        $this->travelToDay(self::TODAY);
        $overdue = $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => '2026-09-28']);

        app(DailyChallengeService::class)->today();

        $overdue->refresh();
        $this->assertSame(ChallengeStatus::Published, $overdue->status);
        $this->assertNull($overdue->publish_date);
    }

    public function test_drafts_are_never_published_automatically(): void
    {
        $this->travelToDay(self::TODAY);
        $draft = $this->makeChallenge(['status' => ChallengeStatus::Draft, 'publish_date' => null]);

        $this->assertNull(app(DailyChallengeService::class)->today());
        $this->assertSame(ChallengeStatus::Draft, $draft->refresh()->status);
    }
}
