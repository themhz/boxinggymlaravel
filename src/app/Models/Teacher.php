<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'bio',
        'hire_date',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];   
    

   public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_teacher','teacher_id', 'class_id')
                    ->withPivot(['role','is_primary'])
                    ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Lessons taught by this teacher, inferred via classes
    public function lessons()
    {
        return $this->hasManyThrough(
            Lesson::class,       // final model
            ClassModel::class,   // through
            'teacher_id',        // FK on classes -> teachers.id
            'id',                // FK on lessons (target key)
            'id',                // local key on teachers
            'lesson_id'          // local key on classes -> lessons.id
        )->distinct();
    }

 

}
