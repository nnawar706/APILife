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
        // delete models on 1st day of every month that are 2 months old and have been read by users
        return static::whereMonth('created_at', Carbon::now('Asia/Dhaka')->subMonths(2)->format('n'))
            ->whereNotNull('read_at');
    }
}
