<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserLoan extends Model
{
    use LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id','completion_status'])
            ->useLogName('User Loan')
            ->logAll()
            ->logOnlyDirty();
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

        static::created(function ($model) {

        });
    }
}
