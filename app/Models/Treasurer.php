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

    public function treasurer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function events()
    {
        return $this->hasMany(TreasurerEvent::class, 'treasurer_id');
    }

    public function liabilities()
    {
        return $this->hasMany(TreasurerLiability::class, 'treasurer_id');
    }
}
