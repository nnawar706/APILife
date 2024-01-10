<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Event extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    protected $casts = [
        'is_public' => 'boolean'
    ];

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

    public function images()
    {
        return $this->hasMany(EventImage::class);
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
}
