<?php
// app/Models/SessionExerciseStudent.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionExerciseStudent extends Model
{
    protected $fillable = [
        "session_id",
        "student_id",
        "session_exercise_id",
        "student_exercise_id",
        "performed_sets",
        "performed_repetitions",
        "performed_weight",
        "performed_duration_seconds",
        "status",
    ];

    public function session()
    {
        return $this->belongsTo(ClassSession::class, "session_id");
    }
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function sessionExercise()
    {
        return $this->belongsTo(SessionExercise::class, "session_exercise_id");
    }
    public function studentExercise()
    {
        return $this->belongsTo(StudentExercise::class, "student_exercise_id");
    }

    // Handy scopes
    public function scopeForSession($q, $sessionId)
    {
        return $q->where("session_id", $sessionId);
    }
    public function scopeForStudent($q, $studentId)
    {
        return $q->where("student_id", $studentId);
    }
}
