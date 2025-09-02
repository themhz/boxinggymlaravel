<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_time',
        'end_time',
        'capacity',
        'is_captured',
        'created_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'is_captured'  => 'boolean',
    ];

    /**
     * Admin user who created this slot.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Appointments booked in this slot.
     * Requires adding slot_id to appointments table.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'slot_id');
    }
}
