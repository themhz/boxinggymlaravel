<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'duration_days',
    ];

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * If you ever want to treat the Plan itself as an offer
     * you can also define a “self-offer” relationship:
     */
    public function selfOffer()
    {
        return $this->hasOne(Offer::class);
    }
}
