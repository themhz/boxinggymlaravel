<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentExercise extends Model
{
    protected $table = 'student_exercises';

    protected $fillable = [
        'student_id','exercise_id',
        'sets','repetitions','weight','duration_seconds','note'
        // 'session_id', // include if you added it
    ];

    protected $casts = [
        'sets' => 'integer',
        'repetitions' => 'integer',
        'weight' => 'decimal:2',
        'duration_seconds' => 'integer',
    ];

    public function student()  { return $this->belongsTo(Student::class); }
    public function exercise() { return $this->belongsTo(Exercise::class); }
    // public function session() { return $this->belongsTo(ClassSession::class); } // if added
}
