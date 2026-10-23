<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Notification;
use App\Services\TwilioSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Yajra\DataTables\Facades\DataTables;

class NotificationController extends Controller
{
    public function __construct(private readonly TwilioSmsService $sms) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $notificationIds = Notification::select(DB::raw('MIN(id) as id'))
                ->groupBy('event_id', 'title', 'message')
                ->pluck('id');

            $notifications = Notification::whereIn('id', $notificationIds)
                ->with('event') // if you want event data
                ->orderByDesc('id')
                ->get();

            return DataTables::of($notifications)
                ->addColumn('event', function ($row) {
                    return $row->event->name ?? '-';
                })
                ->addColumn('notify_date', function ($row) {
                    return optional($row->created_at)->format('Y-m-d') ?? '-';
                })
                ->make(true);
        }

        return view('portal.notification.index');
    }

    public function create()
    {
        $events = Event::get();
        return view('portal.notification.create' , compact('events'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'event_id' => 'required|exists:events,id',
                'title'    => 'required|string',
                'message'  => 'required|string',
                'send_sms' => 'nullable|boolean',
            ]);

            DB::beginTransaction();
            $event = Event::with('teams.users')->findOrFail($validated['event_id']);

            foreach ($event->teams as $team) {
                $userId = $team->pivot->user_id;

                if(isset($userId)){
                    // foreach ($team->users as $user) {
                    Notification::create([
                        'user_id' => $userId,
                        'event_id' => $validated['event_id'],
                        'title' => $validated['title'],
                        'message' => $validated['message'],
                        'is_read' => false,
                    ]);
                    // }
                }

            }

            DB::commit();

            // ── Send SMS to registered anglers if checkbox is checked ───────────────
            if ($request->boolean('send_sms')) {
                $this->sendNotificationSmsToAnglers(
                    (int) $validated['event_id'],
                    $validated['title'],
                    $validated['message']
                );
            }

            return redirect()->route('notification.index')->with('success', 'Notification sent successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    /**
     * Send notification SMS to all registered anglers in the event.
     */
    private function sendNotificationSmsToAnglers(int $eventId, string $title, string $message): void
    {
        $anglers = DB::table('event_team_user')
            ->join('users', 'users.id', '=', 'event_team_user.user_id')
            ->where('event_team_user.event_id', $eventId)
            ->whereNull('event_team_user.deleted_at')
            ->select('users.id', 'users.name', 'users.phone')
            ->distinct()
            ->get();

        $smsBody     = "{$title}\n\n{$message}";
        $sentCount   = 0;
        $failedCount = 0;

        foreach ($anglers as $angler) {
            if (empty($angler->phone)) {
                Log::info('EventNotification SMS: Angler has no phone number — skipping.', [
                    'event_id' => $eventId,
                    'user_id'  => $angler->id,
                    'name'     => $angler->name,
                ]);
                continue;
            }

            $to = $this->toE164($angler->phone);

            try {
                $this->sms->send($to, $smsBody);
                $sentCount++;
            } catch (TwilioException $e) {
                $failedCount++;
                Log::error('EventNotification SMS: Twilio send failed for angler.', [
                    'event_id' => $eventId,
                    'user_id'  => $angler->id,
                    'to'       => $to,
                    'error'    => $e->getMessage(),
                ]);
            } catch (\Throwable $e) {
                $failedCount++;
                Log::error('EventNotification SMS: Unexpected error sending to angler.', [
                    'event_id' => $eventId,
                    'user_id'  => $angler->id,
                    'to'       => $to,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        Log::info('EventNotification SMS broadcast finished.', [
            'event_id'      => $eventId,
            'total_anglers' => $anglers->count(),
            'sent'          => $sentCount,
            'failed'        => $failedCount,
        ]);
    }

    /**
     * Convert phone number to E.164 for Twilio.
     */
    private function toE164(string $phone): string
    {
        $normalized = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (str_starts_with($normalized, '+')) {
            return $normalized;
        }

        $cc = config('services.twilio.default_country_code', '61');

        // Standard local mobile: '0409078051' -> '+61409078051'
        if (str_starts_with($normalized, '0')) {
            return '+' . $cc . substr($normalized, 1);
        }

        // 9-digit AU mobile where Excel/import stripped the leading 0: '402066282' -> '+61402066282'
        if (strlen($normalized) === 9 && str_starts_with($normalized, '4') && $cc === '61') {
            return '+' . $cc . $normalized;
        }

        // Already has country code prefix without '+': '61402066282' -> '+61402066282'
        if (str_starts_with($normalized, $cc)) {
            return '+' . $normalized;
        }

        return '+' . $cc . $normalized;
    }
}
