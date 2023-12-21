<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ExpenseBearer extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'is_sponsored' => 'boolean'
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('event_expense_log'.$model->expense->event_id);
        });

        static::updated(function ($model) {
            Cache::forget('event_expense_log'.$model->expense->event_id);
        });

        static::deleted(function ($model) {
            Cache::forget('event_expense_log'.$model->expense->event_id);
        });
    }
}
