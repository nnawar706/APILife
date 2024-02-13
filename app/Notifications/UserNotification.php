<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserNotification extends Notification
{
    use Queueable;

    public $link, $message, $triggered_by_id, $triggered_by, $triggered_by_image_url;

    /**
     * Create a new notification instance.
     */
    public function __construct($link, $message, $triggered_by_id, $triggered_by, $triggered_by_image_url)
    {
        $this->link                     = $link;                    // link to redirect
        $this->message                  = $message;                 // notification message
        $this->triggered_by_id          = $triggered_by_id;         // auth user id, if present
        $this->triggered_by             = $triggered_by;            // auth username, if present
        $this->triggered_by_image_url   = $triggered_by_image_url;  // auth user profile photo url, if present
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message'                   => $this->message,
            'link'                      => $this->link,
            'triggered_by_id'           => $this->triggered_by_id,
            'triggered_by'              => $this->triggered_by ?? 'Life++', // if auth not present, assign Life++ as triggered by user
            'triggered_by_image_url'    => $this->triggered_by_image_url
        ];
    }
}
