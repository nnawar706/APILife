<?php

namespace App\Models;

use App\Jobs\NotifyUsers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserStory extends Model
{
    use SoftDeletes, Prunable;

    protected $guarded = ['id'];

    protected $hidden = ['updated_at','deleted_at'];

    public function views()
    {
        return $this->hasMany(UserStoryView::class);
    }

    public function viewers()
    {
        return $this->views()->join('users', 'user_story_views.seen_by', '=', 'users.id');
    }

    public function uploadedByInfo()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prunable()
    {
        // delete models on 1st day of every month that are 2 months old
        return static::whereMonth('created_at', Carbon::now('Asia/Dhaka')->subMonths(2)->format('n'));
    }

    public static function boot ()
    {
        parent::boot();

        static::created(function ($model) {
            // if user's current streak is 0, means he didn't add stories in a while
            if ($model->uploadedByInfo->current_streak == 0) {
                $message = auth()->user()->name . " just added a new snap after a while. Don't miss out on the latest moments! 🌟";
            } else {
                $message = "New story added to today's collection. 🌟 Don't miss out the vibrant snap shared by " . auth()->user()->name;
            }

            // count stories that have been uploaded by auth user
            $storyCount = UserStory::whereDate('created_at', $model->created_at)
                ->where('user_id', $model->user_id)->count();

            // if only one story found, means it is the first model created today
            if ($storyCount == 1)
            {
                // increment auth users current streak
                $model->uploadedByInfo->current_streak += 1;
                // save quietly not to trigger any update events
                $model->uploadedByInfo->saveQuietly();

                // notify all users about new story of a user once a day
                dispatch(new NotifyUsers(
                    null,
                    true,
                    'pages/accounts/notification',
                    $message,
                    auth()->user()
                ));
            }
        });
    }
}
