<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    public function classType()
    {
        return $this->belongsTo(ClassType::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
