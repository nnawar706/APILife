<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class InventoryCategory extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'status'        => 'boolean'
    ];

    public function scopeStatus(Builder $q)
    {
        $q->where('status', '=', true);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('inventory_categories');
        });

        static::updated(function ($model) {
            Cache::forget('inventory_categories');
        });

        static::deleted(function ($model) {
            Cache::forget('inventory_categories');

            deleteFile($model->icon_url);
        });
    }
}
