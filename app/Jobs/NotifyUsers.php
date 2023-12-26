<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users, $allUser, $link, $message;

    /**
     * Create a new job instance.
     */
    public function __construct($users, $allUser, $link, $message)
    {
        $this->users   = $users;
        $this->allUser = $allUser;
        $this->link    = $link;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->allUser)
        {
            $users = User::status()->get();

            sendNotification($users, $this->link, $this->message);


        }
    }
}
