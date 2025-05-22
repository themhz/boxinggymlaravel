<?php
// app/Models/Lesson.php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'teacher_id']; // adjust as needed

    public function classes()
    {
        return $this->hasMany(ClassModel::class); // assuming ClassModel is your renamed Program
    }
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class);
    }

}
