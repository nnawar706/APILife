<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class Notification extends Model
{
    use Prunable;

    protected $table = 'notifications';

    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    protected $casts = [
        'id'            => 'string',
        'send_status'   => 'boolean',
        'read_at'       => 'timestamp',
    ];

    public function prunable()
    {
        return static::whereMonth('created_at', '<=', Carbon::now('Asia/Dhaka')->subMonths(3))
            ->where('send_status', true)
            ->whereNotNull('read_at');
    }
}
