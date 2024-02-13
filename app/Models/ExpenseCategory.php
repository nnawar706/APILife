<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseCategory extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'status'        => 'boolean'
    ];

    public function scopeStatus(Builder $q)
    {
        // filter models where status is active
        $q->where('status', '=', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name','icon_url','status'])
            ->useLogName('Expense Category')
            ->logAll();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "An expense category has been {$eventName}";
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }

    public function userExpenses()
    {
        return $this->hasMany(UserExpense::class, 'expense_category_id');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            // invalidate necessary caches
            Cache::forget('expense_categories');
        });

        static::updated(function ($model) {
            // invalidate necessary caches
            Cache::forget('expense_categories');
        });

        static::deleted(function ($model) {
            // invalidate necessary caches
            Cache::forget('expense_categories');
            // delete category image file
            deleteFile($model->icon_url);
        });
    }
}
