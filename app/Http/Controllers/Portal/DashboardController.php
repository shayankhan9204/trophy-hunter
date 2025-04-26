<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Helpers\GoogleHelper;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;


class DashboardController extends Controller
{
    public function dashboard()
    {
        return view('portal.dashboard');
    }

    public function getAccessToken()
    {
        echo GoogleHelper::getGoogleAccessToken();
    }

    public function notificationTest(Request $request)
    {
        $deviceTokens = DeviceToken::select('token', 'user_id', DB::raw('MAX(type) as type'))
            ->groupBy('token', 'user_id')
            ->get();

        foreach ($deviceTokens as $deviceToken) {
            $title = 'Notification Sent';
            $message = 'Notification Sent Successfully';
            $sender = 'FAQs';

            $notificationData = [
                'Sender' => $sender
            ];

            Notification::create([
                'user_id' => $deviceToken->user_id,
                'title' => $title,
                'message' => $message,
                'type' => $deviceToken->type,
                'sender' => $sender,
                'status' => 'new'
            ]);

            SendPushNotificationJob::dispatch($deviceToken->token, $title, $message, $notificationData);
        }

        return response()->json(['message' => 'Notification sent to All users.']);
    }

    public function sendNotificationToUser(Request $request)
    {
        $userId = $request->input('user_id');
        $type = $request->input('type');

        $deviceToken = DeviceToken::where('user_id', $userId)
            ->where('type', $type)
            ->first();

        if ($deviceToken) {
            $title = 'Notification Sent';
            $message = 'Notification Sent Successfully';
            $sender = 'FAQs';

            $notificationData = [
                'Sender' => $sender
            ];

            // Notification table me data insert karein
            Notification::create([
                'user_id' => $deviceToken->user_id,
                'title' => $title,
                'message' => $message,
                'type' => $deviceToken->type,
                'sender' => $sender,
                'status' => 'new'
            ]);

            SendPushNotificationJob::dispatch($deviceToken->token, $title, $message, $notificationData);

            return response()->json(['message' => 'Notification sent to user.']);
        }

        return response()->json(['message' => 'No device token found for this user and type.'], 404);
    }


//    public function notifications(Request $request)
//    {
//        if ($request->ajax()) {
//
//            $query = Notification::query();
//
//            if (!empty($request->user_uid)) {
//                $query->where(function ($q) use ($request) {
//                    $q->whereHas('parent', function ($subQuery) use ($request) {
//                        $subQuery->where('parent_uid', 'LIKE', "%{$request->user_uid}%");
//                    })->orWhereHas('tutor', function ($subQuery) use ($request) {
//                        $subQuery->where('tutor_uid', 'LIKE', "%{$request->user_uid}%");
//                    });
//                });
//            }
//
//            if (!empty($request->user_name)) {
//                $query->where(function ($q) use ($request) {
//                    $q->whereHas('parent', function ($subQuery) use ($request) {
//                        $subQuery->where('full_name', 'LIKE', "%{$request->user_name}%");
//                    })->orWhereHas('tutor', function ($subQuery) use ($request) {
//                        $subQuery->where('full_name', 'LIKE', "%{$request->user_name}%");
//                    });
//                });
//            }
//
//            if (!empty($request->notify_date)) {
//                $query->whereDate('created_at', $request->notify_date);
//            }
//
//            return DataTables::of($query)
//                ->addColumn('user_uid', function ($row) {
//                    if ($row->type === 'parent') {
//                        $parent = DB::table('parents')->where('id', $row->user_id)->first();
//                        return $parent->parent_uid ?? '-';
//                    }
//
//                    if ($row->type === 'tutor') {
//                        $tutor = DB::table('tutors')->where('id', $row->user_id)->first();
//                        return $tutor->tutor_uid ?? '-';
//                    }
//
//                    return '-';
//                })
//                ->addColumn('user_name', function ($row) {
//                    if ($row->type === 'parent') {
//                        $parent = DB::table('parents')->where('id', $row->user_id)->first();
//                        return $parent->full_name ?? '-';
//                    }
//
//                    if ($row->type === 'tutor') {
//                        $tutor = DB::table('tutors')->where('id', $row->user_id)->first();
//                        return $tutor->full_name ?? '-';
//                    }
//
//                    return '-';
//                })
//                ->addColumn('type', function ($row) {
//                    return $row->type ?? '-';
//                })
//                ->addColumn('notification', function ($row) {
//                    return $row->message ?? '-';
//                })
//                ->addColumn('notify_date', function ($row) {
//                    return $row->created_at ? $row->created_at->format('Y-m-d') : '-';
//                })
//                ->make(true);
//        }
//
//        return view('portal.notification.index');
//    }

    public function notifications(Request $request)
    {
        if ($request->ajax()) {
            $query = Notification::query(); // Fix: Use query() instead of all()

            // 🔹 Filter by User UID
            if (!empty($request->user_uid)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('parent', function ($subQuery) use ($request) {
                        $subQuery->where('parent_uid', 'LIKE', "%{$request->user_uid}%");
                    })->orWhereHas('tutor', function ($subQuery) use ($request) {
                        $subQuery->where('tutor_uid', 'LIKE', "%{$request->user_uid}%");
                    });
                });
            }

            // 🔹 Filter by User Name
            if (!empty($request->user_name)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('parent', function ($subQuery) use ($request) {
                        $subQuery->where('full_name', 'LIKE', "%{$request->user_name}%");
                    })->orWhereHas('tutor', function ($subQuery) use ($request) {
                        $subQuery->where('full_name', 'LIKE', "%{$request->user_name}%");
                    });
                });
            }

            // 🔹 Filter by Date
            if (!empty($request->notify_date)) {
                $query->whereDate('created_at', $request->notify_date);
            }

            return DataTables::of($query)
                ->addColumn('type', function ($row) {
                    if ($row->type == 'parent') {
                        return 'Parent';
                    } elseif ($row->type == 'tutor') {
                        return 'Tutor';
                    } else {
                        return $row->type;
                    }
                })
                ->addColumn('user_uid', function ($row) {
                    if ($row->type === 'parent') {
                        return optional($row->parent)->parent_uid ?? '-';
                    }

                    if ($row->type === 'tutor') {
                        return optional($row->tutor)->tutor_uid ?? '-';
                    }

                    return '-';
                })
                ->addColumn('user_name', function ($row) {
                    if ($row->type === 'parent') {
                        return optional($row->parent)->full_name ?? '-';
                    }

                    if ($row->type === 'tutor') {
                        return optional($row->tutor)->full_name ?? '-';
                    }

                    return '-';
                })
                ->addColumn('notification', function ($row) {
                    return $row->message ?? '-';
                })
                ->addColumn('notify_date', function ($row) {
                    return $row->created_at ? $row->created_at->format('Y-m-d') : '-';
                })
                ->make(true);
        }

        return view('portal.notification.index');
    }

}
