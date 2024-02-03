<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStoryView extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at','updated_at'];

    public function story()
    {
        return $this->belongsTo(UserStory::class);
    }

    public function seenByInfo()
    {
        return $this->belongsTo(User::class, 'seen_by');
    }
}
