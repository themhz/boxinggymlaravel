<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSalary extends Model
{
    protected $fillable = [
        'teacher_id','year','month','amount','due_date','is_paid','paid_at','method','notes'
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'is_paid'  => 'boolean',
        'due_date' => 'date',
        'paid_at'  => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}