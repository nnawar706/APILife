<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreasurerLiability extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'boolean'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function treasurer()
    {
        return $this->belongsTo(Treasurer::class, 'treasurer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
