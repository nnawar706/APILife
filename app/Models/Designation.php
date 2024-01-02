<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $guarded = ['id', 'name'];

    public $timestamps = false;

    public function users ()
    {
        return $this->hasMany(User::class);
    }

    public function gradings()
    {
        return $this->hasMany(EventDesignationGrading::class);
    }

}
