<?php

namespace App\Jobs;

use App\Models\Event;
use App\Notifications\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyEventParticipants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event, $user, $link, $message, $sendToLead, $sendToGuests;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event, $user, $link, $message, $sendToLead, $sendToGuests)
    {
        $this->event        = $event;
        $this->user         = $user; // auth user
        $this->link         = $link;
        $this->message      = $message;
        $this->sendToLead   = $sendToLead; // boolean
        $this->sendToGuests = $sendToGuests; // boolean
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $participants = $this->event->participants()->get();

        $lead = $this->event->lead()->first();

        foreach ($participants as $item)
        {
            if (is_null($this->user) || ($this->user && ($item->id != $this->user->id))) {
                $item->notify(new UserNotification(
                    $this->link,
                    $this->message,
                    $this->user ? $this->user->id : null,
                    $this->user ? $this->user->name : 'Life++',
                    $this->user ? $this->user->photo_url : null
                ));
            }
        }

        if ($this->sendToLead && $lead && $lead->id != $this->user->id)
        {
            $lead->notify(new UserNotification(
                $this->link,
                'Congrats! 🌟' . $this->user->name . ' selected you as the leader for ' . $this->event->title . '.',
                $this->user->id,
                $this->user->name,
                $this->user->photo_url
            ));
        }

        if ($this->sendToGuests)
        {
            $auth = $this->user;

            $this->event->guests()->each(function ($guest) use ($auth) {
                if (!$auth || ($auth->id != $guest->id))
                {
                    $guest->notify(new UserNotification(
                        $this->link,
                        $this->message,
                        $auth ? $auth->id : null,
                        $auth ? $auth->name : 'Life++',
                        $auth ? $auth->photo_url : null,
                    ));
                }
            });
        }
    }
}
