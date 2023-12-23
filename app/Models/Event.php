<?php

namespace App\Models;

use App\Jobs\EventPaymentCalculation;
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
            ->useLogName('Event')
            ->logAll()
            ->logOnlyDirty();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "An event has been {$eventName}";
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_user_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_participants', 'event_id');
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

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->event_status_id = 1;
        });

        static::created(function ($model) {
            Cache::forget('events');
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
