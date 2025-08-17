<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',     
        'exercise_type',
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_exercises')
                    ->withTimestamps();
    }

    public function sessions()
    {
        return $this->belongsToMany(ClassSession::class, 'session_exercise')
                    ->withTimestamps();
    }
}