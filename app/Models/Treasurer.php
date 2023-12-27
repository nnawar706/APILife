<?php

namespace App\Models;

use App\Jobs\NotifyUsers;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Treasurer extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'completion_status' => 'boolean'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['user_id','completion_status'])
            ->useLogName('Treasurer')
            ->logAll();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "A treasurer detail has been {$eventName}";
    }

    public function treasurer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events()
    {
        return $this->hasMany(TreasurerEvent::class, 'treasurer_id');
    }

    public function liabilities()
    {
        return $this->hasMany(TreasurerLiability::class, 'treasurer_id');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            dispatch(new NotifyUsers([$model->user_id], false, '', 'You have been selected as a treasurer.'));
        });
    }
}
