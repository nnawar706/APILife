<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;

class UserLoan extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

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

    public function scopeLend(Builder $q)
    {
        $q->where('loan_type', '=', 1);
    }

    public function scopeReturned(Builder $q)
    {
        $q->where('loan_type', '=', 2);
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
}
