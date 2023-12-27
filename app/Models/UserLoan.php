<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserLoan extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    public function scopeAccepted(Builder $q)
    {
        $q->where('status', '=', 1);
    }

    public function scopeDebited(Builder $q)
    {
        $q->where('type', '=', 1);
    }

    public function scopeCredited(Builder $q)
    {
        $q->where('type', '=', 2);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function selectedUser()
    {
        return $this->belongsTo(User::class, 'selected_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id','completion_status'])
            ->useLogName('User Loan')
            ->logAll();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "A user loan has been {$eventName}";
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->code     = 'LN#' . rand(100000, 999999);
            $model->user_id  = auth()->user()->id;
        });

        static::updated(function ($model) {
            Cache::forget('user_loans_summary'.$model->user_id);
        });

        static::deleted(function ($model) {
            Cache::forget('user_loans_summary'.$model->user_id);
        });
    }
}
