<?php
// app/Models/Lesson.php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description']; // adjust as needed

    public function classes()
    {
        return $this->hasMany(ClassModel::class); // assuming ClassModel is your renamed Program
    }
}
