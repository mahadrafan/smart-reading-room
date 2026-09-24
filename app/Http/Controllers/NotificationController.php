<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = UserNotification::where('user_id', Auth::id())
            ->with('loan.book')
            ->orderByDesc('notification_id')
            ->get();

        return view('user.notifications', compact('notifications'));
    }
}
