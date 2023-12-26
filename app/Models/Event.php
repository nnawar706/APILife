<?php

namespace App\Models;

use App\Jobs\NotifyUsers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Event extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];
    protected $hidden = ['updated_at'];

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('F d, Y')
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['event_category_id','lead_user_id','title','detail','from_date','to_date','remarks','event_status_id'])
            ->useLogName('Extravaganza')
            ->logAll();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "An extravaganza detail has been {$eventName}";
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_participants', 'event_id');
    }

    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class, 'event_id');
    }

    public function addParticipants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function designationGradings()
    {
        return $this->hasMany(EventDesignationGrading::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseBearers()
    {
        return $this->hasManyThrough(ExpenseBearer::class, Expense::class, 'event_id', 'expense_id');
    }

    public function expensePayers()
    {
        return $this->hasManyThrough(ExpensePayer::class, Expense::class, 'event_id', 'expense_id');
    }

    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function status()
    {
        return $this->belongsTo(EventStatus::class, 'event_status_id');
    }

    public function treasurer()
    {
        return $this->hasOne(TreasurerEvent::class, 'event_id');
    }


    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->event_status_id = 1;
        });

        static::created(function ($model) {
            Cache::forget('events');
            dispatch(new NotifyUsers(null, true,
                'pages/expense-calculator/extra-vaganza',
                auth()->user()->name .' has created a new extravaganza.'));
        });

        static::updated(function ($model) {
            Cache::forget('events');
            Cache::forget('event_info'.$model->id);
        });

        static::deleted(function ($model) {
            Cache::forget('events');
            Cache::forget('event_info'.$model->id);
        });
    }
}
