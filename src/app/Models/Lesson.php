<?php
// app/Models/Lesson.php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;
    protected $fillable = ['title','description','level','image'];

    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'lesson_id');
    }

    public function teachers()
    {
        // We can't express "many-to-many THROUGH pivot" natively,
        // so we’ll usually load teachers using classes.teachers (see controller below).
        return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id')
                    ->using(\Illuminate\Database\Eloquent\Relations\Pivot::class)
                    ->withTimestamps()
                    ->whereRaw('1 = 0'); // placeholder to discourage misuse
    }

        
}
