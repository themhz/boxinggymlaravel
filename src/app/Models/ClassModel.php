<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassModel extends Model
{
    use HasFactory;
    protected $table = 'classes';
    protected $fillable = [
        'lesson_id',
        'teacher_id',
        'start_time',
        'end_time',
        'day',
        'capacity'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }


    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'class_student',
            'class_id',     // Foreign key on pivot table pointing to Class
            'student_id'    // Foreign key on pivot table pointing to Student
        )->withTimestamps();
    }

    // Each class has many sessions
    // public function sessions()
    // {
    //     return $this->hasMany(ClassSession::class);
    // }
    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'class_id');
    }

    // Each class can have exceptions (like cancelled or rescheduled sessions)
    public function exceptions()
    {
        return $this->hasMany(ClassException::class);
    }
}
