<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'date',
        'start_time',
        'end_time',
        'notes',
    ];

    protected $casts = [
        'date'       => 'date',
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'session_id');
    }

    public function exercises()
    {
        return $this->hasMany(SessionExercise::class, 'session_id');
    }

    // If you still want an alias
    public function relatedClass()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
