<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventImage extends Model
{
    protected $guarded = ['id'];

    public $timestamps = false;

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public static function boot()
    {
        parent::boot();

        static::deleted(function ($model) {
            deleteFile($model->image_url);
            deleteFile($model->thumbnail_url);
        });
    }
}
