<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
     use HasFactory;

    protected $fillable = [
        'class_id',
        'session_date',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }
    // public function exercises()
    // {
    //     return $this->belongsToMany(Exercise::class, 'session_exercise')
    //                 ->withTimestamps();
    // }

    public function exercises()
    {
        return $this->hasMany(SessionExercise::class, 'session_id');
    }    

    // In ClassSession.php
    public function relatedClass()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
    


}
