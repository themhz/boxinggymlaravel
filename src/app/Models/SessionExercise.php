<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SessionExercise extends Model
{
    use HasFactory;

    protected $table = 'session_exercise'; // Explicit, since it doesn’t follow Laravel’s plural rule

    protected $fillable = [
        'session_id',
        'exercise_id',
    ];

    public function session()
    {
        return $this->belongsTo(ClassSession::class, 'session_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }
    
    public function exercises()
    {
        return $this->hasMany(SessionExercise::class, 'session_id');
    }
}
