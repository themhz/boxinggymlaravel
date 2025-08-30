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
        'start_time',
        'end_time',
        'day',
        'capacity',
    ];

    protected $casts = [
        // keep as strings if you store TIME; change to datetime casts only if column types change
        'start_time' => 'string',
        'end_time'   => 'string',
        'capacity'   => 'integer',
    ];

    /** This class belongs to a lesson */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    /** Many teachers via pivot (class_teacher) */
    public function teachers()
    {
         return $this->belongsToMany(
                Teacher::class,
                'class_teacher',
                'class_id', 
                'teacher_id'
            )->withPivot(['role','is_primary'])
            ->withTimestamps();
    }

    /** Many students via pivot (class_student) */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student', 'class_id', 'student_id')
                    ->withPivot(['status', 'note'])
                    ->withTimestamps();
    }

    /** Sessions for this class */
    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'class_id');
    }

    /** Exceptions for this class */
    public function exceptions()
    {
        return $this->hasMany(ClassException::class, 'class_id');
    }
}
