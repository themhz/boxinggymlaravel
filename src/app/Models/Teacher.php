<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'specialty',
        'bio',
        'photo',
    ];




    public function lessons()
    {
        return $this->belongsToMany(Lesson::class);
    }

    public function classes()
    {
        return $this->hasMany(ClassModel::class); // Replace ClassModel with your actual model class name
    }


}
