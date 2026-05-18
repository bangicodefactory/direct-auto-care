<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function getNotifications()
    {
        return auth()->user()
            ->notifications()
            ->latest()
            ->take(10)
            ->get();
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }
}
