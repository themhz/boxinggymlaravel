<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'email', 'user_id']; // Add other fields if needed


    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function classes()
    {
        return $this->belongsToMany(
            ClassModel::class,  // replace with your actual model name
            'class_student',
            'student_id',
            'class_id'
        )->withTimestamps();
    }




}
