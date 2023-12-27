<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
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

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('expense_categories');
        });

        static::deleted(function ($model) {
            Cache::forget('expense_categories');

            deleteFile($model->icon_url);
        });
    }
}
