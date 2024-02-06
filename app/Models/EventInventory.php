<?php

namespace App\Models;

use App\Notifications\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class EventInventory extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    public function scopePending(Builder $q)
    {
        // filter models where approval status is pending
        $q->where('approval_status', '=', 0);
    }

    public function scopeApproved(Builder $q)
    {
        // filter models where approval status is approved
        $q->where('approval_status', '=', 1);
    }

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
            // while inserting new model, add auth user as model's created by user
            $model->created_by = auth()->user()->id;
        });

        static::created(function ($model) {
            // proceed if assigned to user and current logged-in user are not same
            if ($model->assigned_to != auth()->user()->id) {
                // notify the user about someone assigned an inventory to him
                $model->assignedToInfo->notify(new UserNotification(
                    'pages/inventory-approval',
                    auth()->user()->name . ' assigned you to an inventory of ' . $model->event->title . '.',
                    auth()->user()->id,
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });

        static::updating(function ($model) {
            // keep track of who updated the model last
            $model->last_updated_by = auth()->user()->id;
        });

        static::updated(function ($model) {
            // proceed if assigned to user and current logged-in user are not same
            if ($model->assigned_to != auth()->user()->id) {
                // notify the user about someone updated an inventory
                $model->assignedToInfo->notify(new UserNotification(
                    'pages/inventory-approval',
                    auth()->user()->name . ' updated an inventory of ' . $model->event->title . ' that was assigned to you.',
                    auth()->user()->id,
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });

        static::deleted(function ($model) {
            // proceed if assigned to user and current logged-in user are not same
            if ($model->assigned_to != auth()->user()->id) {
                // notify the user about someone deleted an inventory
                $model->assignedToInfo->notify(new UserNotification(
                    'pages/update-vaganza/' . $model->event_id,
                    auth()->user()->name . ' deleted an inventory of ' . $model->event->title . ' that was assigned to you.',
                    auth()->user()->id,
                    auth()->user()->name,
                    auth()->user()->photo_url
                ));
            }
        });
    }
}
