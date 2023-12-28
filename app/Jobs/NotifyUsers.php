<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users, $allUser, $link, $message, $user;

    /**
     * Create a new job instance.
     */
    public function __construct($users, $allUser, $link, $message, $user)
    {
        $this->users   = $users;
        $this->allUser = $allUser;
        $this->link    = $link;
        $this->message = $message;
        $this->user    = $user; // auth user
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->allUser)
        {
            $users = User::status()->get();
        }
        else
        {
            $users = User::whereIn('id', $this->users)->get();
        }

        if (count($users) != 0)
        {
            foreach ($users as $item)
            {
                $item->notify(new UserNotification(
                    $this->link,
                    $this->message,
                    $this->user->name,
                    $this->user->photo_url
                ));
            }
        }
    }
}
