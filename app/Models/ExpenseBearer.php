<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseBearer extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'is_sponsored' => 'boolean'
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
