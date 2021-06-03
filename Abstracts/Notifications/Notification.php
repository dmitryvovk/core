<?php

namespace Apiato\Core\Abstracts\Notifications;

use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Config;

abstract class Notification extends LaravelNotification
{
    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return Config::get('notification.channels');
    }
}
