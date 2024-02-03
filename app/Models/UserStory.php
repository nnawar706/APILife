<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserStory extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['updated_at','deleted_at'];

    public function views()
    {
        return $this->hasMany(UserStoryView::class);
    }

    public function uploadedByInfo()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function boot ()
    {
        parent::boot();

        static::created(function ($model) {
            $storyCount = UserStory::whereDate('created_at', $model->created_at)
                ->where('user_id', $model->user_id)->count();

            if ($storyCount == 1)
            {
                $model->uploadedByInfo->current_streak += 1;
                $model->uploadedByInfo->saveQuietly();
            }
        });
    }
}
