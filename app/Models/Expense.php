<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        });

        static::updated(function ($model) {
            Cache::forget('event_info'.$model->event_id);
            Cache::forget('event_expense_log'.$model->event_id);
        });

        static::deleted(function ($model) {
            Cache::forget('event_info'.$model->event_id);
            Cache::forget('event_expense_log'.$model->event_id);
        });
    }
}
