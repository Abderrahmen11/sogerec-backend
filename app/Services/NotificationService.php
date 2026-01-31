<?php

namespace App\Services;

use Illuminate\Support\Facades\Notification;
use App\Models\User;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a notification instance to all admins.
     */
    public function sendToAdmins(BaseNotification $notification): void
    {
        try {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, $notification);
            }
        } catch (\Exception $e) {
            Log::error('NotificationService::sendToAdmins failed: ' . $e->getMessage());
        }
    }

    /**
     * Send directly to a single user instance.
     */
    public function sendToUser(User $user, BaseNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (\Exception $e) {
            Log::error('NotificationService::sendToUser failed: ' . $e->getMessage());
        }
    }
}
