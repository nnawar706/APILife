<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Notifications\UserNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NotifyUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users, $allUser, $link, $message, $user;

    /**
     * Create a new job instance.
     */
    public function __construct($users, $allUser, $link, $message, $user)
    {
        $this->users   = $users;    // array of user ids to whom notification needs to be sent
        $this->allUser = $allUser;  // boolean, if then notification needs to be sent to all users
        $this->link    = $link;     // link to redirect
        $this->message = $message;  // notification message
        $this->user    = $user;     // auth user
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // if allUser flag is true fetch all active users
        if ($this->allUser)
        {
            $users = User::status()->get();
        }
        // else fetch users whose ids have been passed
        else
        {
            $users = User::whereIn('id', $this->users)->get();
        }

        foreach ($users as $item)
        {
            // send notification if user is not auth user
            if (!$this->user || ($this->user->id != $item->id))
            {
                $item->notify(new UserNotification(
                    $this->link,
                    $this->message,
                    $this->user ? $this->user->id : null,
                    $this->user ? $this->user->name : 'Life++',
                    $this->user ? $this->user->photo_url : null
                ));
            }
        }
    }
}
