<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Offer;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'offer_id',
        'start_date',
        'end_date',
        'amount',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
