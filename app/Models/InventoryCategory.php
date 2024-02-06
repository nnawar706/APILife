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
        // filter models where status is active
        $q->where('status', '=', true);
    }

    public function inventories()
    {
        return $this->hasMany(EventInventory::class);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            // invalidate necessary caches
            Cache::forget('inventory_categories');
        });

        static::updated(function ($model) {
            // invalidate necessary caches
            Cache::forget('inventory_categories');
        });

        static::deleted(function ($model) {
            // invalidate necessary caches
            Cache::forget('inventory_categories');
            // delete category image file
            deleteFile($model->icon_url);
        });
    }
}
