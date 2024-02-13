<?php

namespace App\Jobs;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Notifications\UserNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyEventParticipants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event, $user, $link, $message, $sendToLead, $sendToGuests;

    /**
     * Create a new job instance.
     */
    public function __construct(Event $event, $user, $link, $message, $sendToLead, $sendToGuests)
    {
        $this->event        = $event;        // event model
        $this->user         = $user;         // auth user
        $this->link         = $link;         // link to redirect
        $this->message      = $message;      // notification message
        $this->sendToLead   = $sendToLead;   // boolean
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
            // notify all participants except auth user
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

        // notify event lead if sendToLead flag is true and auth user is not selected as lead
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

        // if sendToGuests flag is true, notify event guests
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
