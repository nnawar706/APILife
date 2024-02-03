<?php

namespace App\Models;

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
}
