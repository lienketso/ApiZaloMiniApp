<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Member extends Model
{
    use HasFactory;
    protected $fillable = [
        'club_id',
        'name',
        'phone',
        'email',
        'avatar',
        'role',
        'status',
        'joined_date'
    ];

    protected $casts = [
        'joined_date' => 'date',
    ];

    // Relationships
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'attendance');
    }

    /**
     * Get the clubs that this member belongs to
     */
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_member')
                    ->withPivot('role', 'joined_date', 'notes', 'is_active')
                    ->withTimestamps();
    }

    /**
     * Get the club member relationships
     */
    public function clubMemberships()
    {
        return $this->hasMany(ClubMember::class);
    }

    // Scopes
    public function scopeByClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

}
