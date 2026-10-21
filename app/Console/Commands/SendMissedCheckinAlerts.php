<?php

namespace App\Console\Commands;

use App\Models\EventDate;
use App\Services\MissedCheckinSmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scans all active event dates and fires missed check-in SMS alerts
 * for any I'm Safe interval whose window has already closed AND whose
 * 10-minute grace period has elapsed.
 *
 * Timeline example (im_safe_interval = 30 min, event start = 05:00):
 *
 *   Interval 1  window 05:00–05:30  →  SMS fires from 05:40 until 06:10
 *   Interval 2  window 05:30–06:00  →  SMS fires from 06:10 until 06:40
 *   Interval 3  window 06:00–06:30  →  SMS fires from 06:40 until 07:10
 *
 * The scheduler runs this every minute; the service's idempotency layer
 * (MissedCheckinAlertSent) ensures each contact is only messaged once
 * per interval — even if the command overlaps or is retried.
 *
 * Usage:
 *   php artisan missed-checkin:send-alerts            # process current due interval
 *   php artisan missed-checkin:send-alerts --dry-run  # log what would fire, send nothing
 */
class SendMissedCheckinAlerts extends Command
{
    /**
     * Minutes to wait after an interval window closes before sending the SMS.
     * Matches the product requirement: "send SMS 10 minutes after I'm Safe interval passes".
     */
    private const GRACE_MINUTES = 10;

    protected $signature = 'missed-checkin:send-alerts
                            {--dry-run : Log which intervals would fire without sending any SMS}';

    protected $description = "Send SMS alerts for I'm Safe intervals that closed more than " . self::GRACE_MINUTES . " minutes ago without a check-in";

    public function __construct(private readonly MissedCheckinSmsService $smsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $now      = Carbon::now();

        $this->info(
            "Running missed check-in alert scan (grace=" . self::GRACE_MINUTES . "min)" .
            ($isDryRun ? ' [DRY RUN]' : '') .
            " at {$now->toDateTimeString()}"
        );

        // Load all event dates that:
        //   • belong to an event with has_im_safe = true
        //   • have a positive im_safe_interval
        //   • whose date is today or in the past (event has started)

        $eventDates = EventDate::query()
            ->whereHas('event', fn ($q) => $q->where('has_im_safe', true)->whereNull('deleted_at'))
            ->where('im_safe_interval', '>', 0)
            ->where('date', $now->toDateString())
            ->with('event')
            ->get();

        if ($eventDates->isEmpty()) {
            $this->info("No active event dates with I'm Safe enabled — nothing to do.");
            return self::SUCCESS;
        }

        $totalFired = 0;

        foreach ($eventDates as $eventDate) {
            $totalFired += $this->processEventDate($eventDate, $now, $isDryRun);
        }

        $this->info("Done. Intervals processed: {$totalFired}");

        return self::SUCCESS;
    }

    /**
     * Find the ONE interval that is currently due (including grace period) for
     * this event date and process it.
     *
     * How the grace period works
     * ──────────────────────────
     * We subtract GRACE_MINUTES from $now to get $effectiveNow, then run the
     * floor() calculation against that shifted time. This makes the command
     * "see" the world as if it were 10 minutes earlier, so an interval only
     * becomes "completed" (and therefore fireable) once 10 real minutes have
     * passed since its window closed.
     *
     * Example (interval = 30 min, grace = 10 min, event start = 05:00):
     *
     *   Real now  │ effectiveNow │ floor(elapsed/30) │ Action
     *   ──────────┼──────────────┼───────────────────┼───────────────────────────
     *   05:35     │ 05:25        │ 0                 │ nothing due yet
     *   05:40     │ 05:30        │ 1                 │ fire interval 1 ✅
     *   06:05     │ 05:55        │ 1                 │ still interval 1 (idempotent)
     *   06:10     │ 06:00        │ 2                 │ fire interval 2 ✅
     *   06:15     │ 06:05        │ 2                 │ still interval 2 (idempotent)
     *   06:40     │ 06:30        │ 3                 │ fire interval 3 ✅
     *
     * Anti-backfill guard
     * ───────────────────
     * Each interval's processing window expires when the NEXT interval's SMS
     * would be due (nextWindowEnd + GRACE_MINUTES). After that, the interval is
     * silently skipped — we never backfill.
     *
     * Returns 1 if an interval was (or would be) processed, 0 otherwise.
     */
    private function processEventDate(EventDate $eventDate, Carbon $now, bool $isDryRun): int
    {
        $eventStart      = Carbon::parse("{$eventDate->date} {$eventDate->start_time}");
        $intervalMinutes = (int) $eventDate->im_safe_interval;

        // ── 1. Shift now back by grace period ────────────────────────────────────
        //    effectiveNow is what the floor() sees. An interval only becomes
        //    "complete" to the formula once GRACE_MINUTES real minutes have
        //    elapsed since its window closed.
        $effectiveNow = $now->copy()->subMinutes(self::GRACE_MINUTES);

        // ── 2. Event hasn't been running long enough for any SMS yet ──────────────
        $elapsedMinutes = (int) $eventStart->diffInMinutes($effectiveNow, false);
        if ($elapsedMinutes < 0) {
            // effectiveNow is still before the event start — grace not elapsed yet
            return 0;
        }

        // ── 3. Derive the single interval that is currently due ───────────────────
        //
        //   floor(effectiveElapsed / intervalMinutes) = intervals fully completed
        //   as seen by the grace-shifted clock. That number is the 1-based
        //   interval number of the interval whose SMS is now due.
        //
        $completedCount = (int) floor($elapsedMinutes / $intervalMinutes);

        if ($completedCount === 0) {
            // Grace period has elapsed since event start but not since interval 1
            return 0;
        }

        $intervalNumber = $completedCount; // 1-based

        // ── 4. Compute this interval's exact window boundaries (real time) ────────
        $windowStart = $eventStart->copy()->addMinutes($intervalMinutes * ($intervalNumber - 1));
        $windowEnd   = $windowStart->copy()->addMinutes($intervalMinutes);
        $dueAt       = $windowEnd->copy()->addMinutes(self::GRACE_MINUTES);  // real SMS send time

        // ── 5. Guard: respect event end_time if set ──────────────────────────────
        if (! empty($eventDate->end_time)) {
            $eventEnd = Carbon::parse("{$eventDate->date} {$eventDate->end_time}");
            if ($windowStart->greaterThanOrEqualTo($eventEnd)) {
                $this->line(sprintf(
                    '  event_date_id=%d: interval %d starts at or after event end_time — skipping.',
                    $eventDate->id,
                    $intervalNumber,
                ));
                return 0;
            }
        }

        // ── 6. Anti-backfill guard ────────────────────────────────────────────────
        //    This interval's processing window expires when the NEXT interval's
        //    SMS becomes due: nextWindowEnd + GRACE_MINUTES.
        //    After that point, we skip silently — no backfilling ever.
        $nextDueAt = $windowEnd->copy()->addMinutes($intervalMinutes + self::GRACE_MINUTES);
        if ($now->greaterThanOrEqualTo($nextDueAt)) {
            $this->line(sprintf(
                '  event_date_id=%d: interval %d SMS window expired at %s — skipping (no backfill).',
                $eventDate->id,
                $intervalNumber,
                $nextDueAt->toTimeString(),
            ));
            return 0;
        }

        // ── 7. Fire ──────────────────────────────────────────────────────────────
        if ($isDryRun) {
            $this->line(sprintf(
                '  [DRY RUN] Would send SMS for event_date_id=%d interval=%d | window %s–%s | due at %s',
                $eventDate->id,
                $intervalNumber,
                $windowStart->toTimeString(),
                $windowEnd->toTimeString(),
                $dueAt->toTimeString(),
            ));

            Log::info('MissedCheckin [dry-run]: Would process interval.', [
                'event_date_id'   => $eventDate->id,
                'interval_number' => $intervalNumber,
                'window_start'    => $windowStart->toDateTimeString(),
                'window_end'      => $windowEnd->toDateTimeString(),
                'sms_due_at'      => $dueAt->toDateTimeString(),
            ]);
        } else {
            $this->line(sprintf(
                '  Sending SMS for event_date_id=%d interval=%d | window %s–%s | due at %s',
                $eventDate->id,
                $intervalNumber,
                $windowStart->toTimeString(),
                $windowEnd->toTimeString(),
                $dueAt->toTimeString(),
            ));

            try {
                $this->smsService->processInterval($eventDate, $intervalNumber);
            } catch (\Throwable $e) {
                $this->error("  Failed for event_date_id={$eventDate->id} interval={$intervalNumber}: {$e->getMessage()}");

                Log::error('MissedCheckin: Unhandled exception in command.', [
                    'event_date_id'   => $eventDate->id,
                    'interval_number' => $intervalNumber,
                    'error'           => $e->getMessage(),
                ]);

                return 0;
            }
        }

        return 1;
    }
}
