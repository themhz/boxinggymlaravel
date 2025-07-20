<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Offer;

class StudentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method_id',
        'membership_plan_id',
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

    public function membershipPlan()
    {
        return $this->belongsTo(MembershipPlan::class);
    }


    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payments()
    {
        return $this->hasMany(StudentPayment::class);
    }
}
