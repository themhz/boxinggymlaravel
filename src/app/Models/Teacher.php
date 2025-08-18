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
    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_teacher', 'teacher_id', 'class_id')
                    ->withTimestamps();
    }

    // Optional convenience: derive lessons via classes (read-only)
    public function lessons()
    {
        // uses the classes pivot; read-only, don’t attach/sync via this
        return $this->belongsToMany(Lesson::class, 'classes', 'id', 'lesson_id')
                    ->distinct();
        // ^ This is just a shortcut query; prefer joining via classes when you need details.
    }


    public function salaries()
    {
        return $this->hasMany(TeacherSalary::class);
    }


 

}
