<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class UserBudget extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function boot() {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('budget_summary' . $model->user_id);
        });

        static::updated(function ($model) {
            Cache::forget('budget_summary' . $model->user_id);
        });
    }
}
