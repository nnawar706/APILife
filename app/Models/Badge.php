<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges', 'badge_id');
    }

    public function userBadge()
    {
        return $this->hasMany(UserBadge::class, 'badge_id');
    }
}
