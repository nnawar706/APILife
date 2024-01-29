<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UserExpense extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('F d, Y')
        );
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = auth()->user()->id;
        });

        static::created(function ($model) {
            Cache::forget('user_expense' . $model->user_id);
            Cache::forget('budget_summary' . $model->user_id);
        });

        static::updated(function ($model) {
            Cache::forget('user_expense' . $model->user_id);
            Cache::forget('budget_summary' . $model->user_id);
        });

        static::deleted(function ($model) {
            Cache::forget('user_expense' . $model->user_id);
            Cache::forget('budget_summary' . $model->user_id);
        });
    }
}
