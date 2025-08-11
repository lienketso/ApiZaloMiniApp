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
        'created_by'
    ];

    protected $casts = [
        'is_setup' => 'boolean',
    ];

    /**
     * Get the user who created the club
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Get the club's members through pivot table
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'club_member', 'club_id', 'member_id')
                    ->withPivot('role', 'joined_date', 'notes', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Get the club's member relationships
     */
    public function clubMembers()
    {
        return $this->hasMany(ClubMember::class);
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
}
