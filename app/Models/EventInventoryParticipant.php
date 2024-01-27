<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventInventoryParticipant extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = [
        'approval_status' => 'boolean'
    ];

    public function eventInventory()
    {
        return $this->belongsTo(EventInventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
