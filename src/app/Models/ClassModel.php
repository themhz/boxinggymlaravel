<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassModel extends Model
{
    use HasFactory;
    protected $table = 'classes';
    protected $fillable = [
        'lesson_id', 'start_time', 'end_time', 'day', 'capacity'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
