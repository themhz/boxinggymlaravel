<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'email', 'user_id']; // Add other fields if needed

    
    public function classes()
    {
        return $this->belongsToMany(
            ClassModel::class, 
            'class_student',
            'student_id',      
            'class_id',
      
        )->withTimestamps();
    }

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class, 'student_exercise')
                    ->withTimestamps();
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
