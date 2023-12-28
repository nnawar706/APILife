<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserNotification extends Notification
{
    use Queueable;

    public $link, $message, $triggered_by, $triggered_by_image_url;

    /**
     * Create a new notification instance.
     */
    public function __construct($link, $message, $triggered_by, $triggered_by_image_url)
    {
        $this->link                     = $link;
        $this->message                  = $message;
        $this->triggered_by             = $triggered_by;
        $this->triggered_by_image_url   = $triggered_by_image_url;
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
            'triggered_by'              => $this->triggered_by,
            'triggered_by_image_url'    => $this->triggered_by_image_url
        ];
    }
}
