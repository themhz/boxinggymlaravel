<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_id',
        'name',
        'email',
        'phone',        
        'notes',
        'status',
    ];
    

    public function slot()
    {
        return $this->belongsTo(AppointmentSlot::class, 'slot_id');
    }
}
