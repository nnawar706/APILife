<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStory extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    public function views()
    {
        return $this->hasMany(UserStoryView::class);
    }

    public function uploadedByInfo()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
