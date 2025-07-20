<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeacherSalary extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'amount', 'pay_date', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
