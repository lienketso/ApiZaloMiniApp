<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sport',
        'logo',
        'address',
        'phone',
        'email',
        'description',
        'bank_name',
        'account_name',
        'account_number',
        'is_setup',
        'created_by',
        'trial_expired_at',
        'subscription_expired_at',
        'subscription_status',
        'plan_id',
        'last_payment_at'
    ];

    protected $casts = [
        'is_setup' => 'boolean',
        'trial_expired_at' => 'datetime',
        'subscription_expired_at' => 'datetime',
        'last_payment_at' => 'datetime',
    ];

    /**
     * Get the user who created the club
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the club's current plan
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the club's users through user_clubs pivot table
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_clubs')
                    ->withPivot('role', 'joined_date', 'notes', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Get the club's user relationships
     */
    public function userClubs()
    {
        return $this->hasMany(UserClub::class);
    }

    /**
     * Get the club's events
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the club's fund transactions
     */
    public function fundTransactions()
    {
        return $this->hasMany(FundTransaction::class);
    }

    /**
     * Get the club's matches
     */
    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    /**
     * Check if club is fully set up
     */
    public function isFullySetup()
    {
        return $this->is_setup &&
            !empty($this->name) &&
            !empty($this->sport) &&
            !empty($this->address);
    }

    /**
     * Check if club is in trial period
     */
    public function isInTrial()
    {
        return $this->subscription_status === 'trial' && 
               $this->trial_expired_at && 
               $this->trial_expired_at->isFuture();
    }

    /**
     * Check if club has active subscription
     */
    public function hasActiveSubscription()
    {
        return $this->subscription_status === 'active' && 
               $this->subscription_expired_at && 
               $this->subscription_expired_at->isFuture();
    }

    /**
     * Check if club subscription is expired
     */
    public function isSubscriptionExpired()
    {
        return $this->subscription_status === 'expired' || 
               ($this->subscription_expired_at && $this->subscription_expired_at->isPast());
    }

    /**
     * Start trial period for club (1 month)
     */
    public function startTrial()
    {
        $this->update([
            'subscription_status' => 'trial',
            'trial_expired_at' => now()->addMonth(),
            'plan_id' => null,
            'subscription_expired_at' => null,
            'last_payment_at' => null
        ]);
    }

    /**
     * Activate subscription for club
     */
    public function activateSubscription($planId, $durationDays = null)
    {
        $plan = Plan::find($planId);
        if (!$plan) {
            throw new \Exception('Plan not found');
        }

        $duration = $durationDays ?? $plan->duration_days;
        
        $this->update([
            'subscription_status' => 'active',
            'plan_id' => $planId,
            'subscription_expired_at' => now()->addDays($duration),
            'trial_expired_at' => null,
            'last_payment_at' => now()
        ]);
    }

    /**
     * Cancel subscription for club
     */
    public function cancelSubscription()
    {
        $this->update([
            'subscription_status' => 'canceled'
        ]);
    }

    /**
     * Check if club can access premium features
     */
    public function canAccessPremiumFeatures()
    {
        return $this->isInTrial() || $this->hasActiveSubscription();
    }
}
