<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $guarded = ['id', 'name'];

    protected $hidden = ['created_at', 'updated_at'];

    public function users ()
    {
        return $this->hasMany(User::class);
    }

    public function gradings()
    {
        return $this->hasMany(EventDesignationGrading::class);
    }

}
