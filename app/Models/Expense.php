<?php

namespace App\Models;

use App\Jobs\NotifyEventParticipants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Expense extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

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

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('event_info'.$model->event_id);
            Cache::forget('event_expense_log'.$model->event_id);

            dispatch(new NotifyEventParticipants(
                $model->event,
                auth()->user(),
                'pages/timeline-page/'.$model->event_id,
                auth()->user()->name . ' has added new expense to ' . $model->event->title,
                false
            ));
        });

        static::updated(function ($model) {
            Cache::forget('event_info'.$model->event_id);
            Cache::forget('event_expense_log'.$model->event_id);
        });

        static::deleted(function ($model) {
            Cache::forget('expense'.$model->id);
            Cache::forget('event_info'.$model->event_id);
            Cache::forget('event_expense_log'.$model->event_id);

            dispatch(new NotifyEventParticipants(
                $model->event,
                auth()->user(),
                'pages/timeline-page/'.$model->event_id,
                auth()->user()->name . ' has removed an expense data from ' . $model->event->title,
                false
            ));
        });
    }
}
