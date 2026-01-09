<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
                                     ->orderBy('created_at', 'desc')
                                     ->get();

        return response()->make(
            response()->json($notifications)->getContent(),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Mark all notifications as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', Auth::id())->where('read', false)->update(['read' => true]);

        return response()->make(
            response()->json(['message' => 'All notifications marked as read'])->getContent(),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}