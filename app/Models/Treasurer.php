<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treasurer extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'completion_status' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(TreasurerEvent::class, 'treasurer_id');
    }

    public function treasurerEvents()
    {
        return $this->hasMany(TreasurerEvent::class, 'treasurer_id');
    }
}
