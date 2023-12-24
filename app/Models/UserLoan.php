<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoan extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function selectedUser()
    {
        return $this->belongsTo(User::class, 'selected_user_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->code     = 'LN#' . rand(100000, 999999);
            $model->user_id  = auth()->user()->id;
        });

        static::created(function ($model) {

        });
    }
}
