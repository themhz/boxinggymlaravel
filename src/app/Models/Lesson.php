<?php
// app/Models/Lesson.php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
   public function classes()
    {
        return $this->hasMany(ClassModel::class, 'lesson_id');
    }

    // Teachers who teach this lesson, inferred via classes
    public function teachers()
    {
        return $this->hasManyThrough(
            Teacher::class,
            ClassModel::class,
            'lesson_id',   // classes.lesson_id -> lessons.id
            'id',          // teachers.id
            'id',          // lessons.id
            'teacher_id'   // classes.teacher_id -> teachers.id
        )->distinct();
    }

}
