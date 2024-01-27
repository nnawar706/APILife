<?php

namespace App\Models;

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

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_inventory_participants', 'event_inventory_id');
    }
}
