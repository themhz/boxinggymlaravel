<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'duration_days',
    ];

    // Optional: relation to payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
