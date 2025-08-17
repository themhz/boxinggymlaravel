<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'membership_plan_id',
        'title',
        'description',
        'discount_amount',
        'discount_percent',
        'starts_at',
        'ends_at',
    ];
    protected $casts = [
        'discount_amount'  => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];
    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    /**
     * Helper to see if this offer is active now:
     */
    public function isActive(): bool
    {
        $today = now()->toDateString();
        return (!$this->starts_at || $this->starts_at <= $today)
            && (!$this->ends_at   || $this->ends_at   >= $today);
    }

    public function membershipPlan()
    {
        return $this->belongsTo(\App\Models\MembershipPlan::class);
    }
}

