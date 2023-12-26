<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Prunable;

class Notification
{
    use Prunable;

    protected $table = 'notifications';

    protected $guarded = ['id'];

    protected $hidden = ['updated_at'];

    protected $casts = [
        'id'            => 'string',
        'send_status'   => 'boolean',
        'read_at'       => 'timestamp',
        'created_at'    => 'timestamp'
    ];

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth(3))
            ->whereNull('read_at');
    }
}
