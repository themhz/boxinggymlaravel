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
    'team_id',
];


    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function classTypes()
    {
        return $this->hasMany(ClassType::class);
    }

    public function lessons()
    {
        return $this->belongsToMany(Lesson::class);
    }

}
