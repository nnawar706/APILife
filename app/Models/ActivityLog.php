<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ActivityLog extends Model
{
    use Prunable;

    protected $table = 'activity_log';

    public function prunable()
    {
        return static::whereMonth('created_at', '<=', Carbon::now('Asia/Dhaka')->subMonths(3));
    }
}
