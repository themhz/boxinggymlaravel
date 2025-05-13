<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentAvailability extends Model
{
    protected $table = 'appointment_availability'; // exact table name
    protected $fillable = ['day', 'start_time', 'is_available'];
}
