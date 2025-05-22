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

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }


    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'class_student',
            'class_id',
            'teacher_id',
            'student_id'
        )->withTimestamps();
    }
}
