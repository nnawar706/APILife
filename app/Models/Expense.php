<?php

namespace App\Models;

use App\Jobs\NotifyEventParticipants;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Expense extends Model
{
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
            $model->created_by = auth()->user()->id;
        });

        static::created(function ($model) {
            Cache::forget('event_expenses'.$model->event_id);

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
            Cache::forget('expense_info'.$model->id);
            Cache::forget('event_expenses'.$model->event_id);

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
