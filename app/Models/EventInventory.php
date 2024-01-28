<?php

namespace App\Models;

use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Model;

class EventInventory extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function createdByInfo()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByInfo()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    public function assignedToInfo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->user()->id;
        });

        static::created(function ($model) {
            if ($model->assigned_to != auth()->user()->id) {
                $model->assignedToInfo->notify(new UserNotification(
                    'pages/inventory-approval',
                    auth()->user()->name . ' assigned you to an inventory of ' . $model->event->title . '.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });

        static::updating(function ($model) {
            $model->last_updated_by = auth()->user()->id;
        });

        static::updated(function ($model) {
            if ($model->assigned_to != auth()->user()->id) {
                $model->assignedToInfo->notify(new UserNotification(
                    'pages/inventory-approval',
                    auth()->user()->name . ' updated an inventory of ' . $model->event->title . ' that was assigned to you.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });

        static::deleted(function ($model) {
            if ($model->assigned_to != auth()->user()->id) {
                $model->assignedToInfo->notify(new UserNotification(
                    'pages/update-vaganza/' . $model->event_id,
                    auth()->user()->name . ' deleted an inventory of ' . $model->event->title . ' that was assigned to you.',
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });
    }
}
