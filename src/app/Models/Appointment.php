<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'status',
        'notes',
    ];
    protected $guarded = [];


    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lessons()
    {
        return $this->belongsTo(Lesson::class);
    }
}
