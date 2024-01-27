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

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_inventory_participants', 'event_inventory_id');
    }

    public function inventoryParticipants()
    {
        return $this->hasMany(EventInventoryParticipant::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->user()->id;
        });

        static::updating(function ($model) {
            $model->last_updated_by = auth()->user()->id;
        });

        static::updated(function ($model) {
            $model->inventoryParticipants()->where('approval_status', 1)->update([
                'approval_status' => 0
            ]);

            $model->inventoryParticipants()->each(function ($participant) use ($model) {
                if ($participant->user_id != auth()->user()->id)
                {
                    $participant->user->notify(new UserNotification(
                        'pages/update-vaganza/' . $model->event_id,
                        auth()->user()->name . ' updated an inventory from ' . $model->event->title . '.',
                        auth()->user()->name,
                        auth()->user()->photo_url
                    ));
                }
            });
        });
    }
}
