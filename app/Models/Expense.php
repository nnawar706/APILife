<?php

namespace App\Models;

use Carbon\Carbon;
use Spatie\Activitylog\LogOptions;
use App\Jobs\NotifyEventParticipants;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Expense extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('F d, Y')
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('F d, Y')
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['expense_category_id','title','unit_cost','quantity','remarks'])
            ->useLogName('Extravaganza Expense')
            ->logAll();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "An extravaganza expense has been {$eventName}";
    }

    public function bearers()
    {
        return $this->hasMany(ExpenseBearer::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function payers()
    {
        return $this->hasMany(ExpensePayer::class);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function createdByInfo()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastUpdatedByInfo()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // while new expense, add auth user as model's created by user
            $model->created_by = auth()->user()->id;
        });

        static::created(function ($model) {
            // invalidate necessary caches
            Cache::forget('event_expenses'.$model->event_id);

            // notify participants about newly added expense
            dispatch(new NotifyEventParticipants(
                $model->event,
                auth()->user(),
                'pages/timeline-page/'.$model->event_id,
                auth()->user()->name . ' added new expense to ' . $model->event->title,
                false,
                false
            ));
        });

        static::deleted(function ($model) {
            // invalidate necessary caches
            Cache::forget('expense_info'.$model->id);
            Cache::forget('event_expenses'.$model->event_id);

            // notify participants about deleted expense
            dispatch(new NotifyEventParticipants(
                $model->event,
                auth()->user(),
                'pages/timeline-page/'.$model->event_id,
                auth()->user()->name . ' removed an expense data from ' . $model->event->title,
                false,
                false
            ));
        });
    }
}
