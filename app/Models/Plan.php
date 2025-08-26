<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'duration_days',
        'features',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the clubs that are using this plan
     */
    public function clubs()
    {
        return $this->hasMany(Club::class);
    }

    /**
     * Check if plan is currently active
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Get formatted billing cycle
     */
    public function getFormattedBillingCycleAttribute()
    {
        return ucfirst($this->billing_cycle);
    }

    /**
     * Get duration in months
     */
    public function getDurationMonthsAttribute()
    {
        return round($this->duration_days / 30, 1);
    }
}
