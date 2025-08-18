<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'membership_plan_id', 'offer_id', 'payment_method_id',
        'start_date', 'end_date', 'amount',
    ];

    // Owner
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Relateds
    public function membershipPlan()
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
