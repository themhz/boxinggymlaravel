<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassException extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'exception_date',
        'is_cancelled',
        'override_start_time',
        'override_end_time',
        'reason',
    ];

    protected $casts = [
        'exception_date'      => 'date',
        'is_cancelled'        => 'boolean',
        // Option A: store times as plain strings
        'override_start_time' => 'string',
        'override_end_time'   => 'string',
        'reason' =>'string',
        // — or —

        // Option B: cast them as datetimes with format
        // 'override_start_time' => 'datetime:H:i:s',
        // 'override_end_time'   => 'datetime:H:i:s',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
