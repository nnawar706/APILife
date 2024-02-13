<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserIncome extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('F d, Y')
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = auth()->user()->id;
        });

        static::created(function ($model) {
            Cache::forget('user_income' . $model->user_id);
            Cache::forget('budget_summary' . $model->user_id);
        });

        static::updated(function ($model) {
            Cache::forget('user_income' . $model->user_id);
            Cache::forget('budget_summary' . $model->user_id);
        });

        static::deleted(function ($model) {
            Cache::forget('user_income' . $model->user_id);
            Cache::forget('budget_summary' . $model->user_id);
        });
    }
}
