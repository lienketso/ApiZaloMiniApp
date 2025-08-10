<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubMember extends Model
{
    use HasFactory;

    protected $table = 'club_member';

    protected $fillable = [
        'club_id',
        'member_id',
        'role',
        'joined_date',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'joined_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // Role constants
    const ROLE_MEMBER = 'member';
    const ROLE_ADMIN = 'admin';
    const ROLE_GUEST = 'guest';

    // Get role options
    public static function getRoleOptions()
    {
        return [
            self::ROLE_MEMBER => 'Thành viên',
            self::ROLE_ADMIN => 'Quản trị viên',
            self::ROLE_GUEST => 'Khách',
        ];
    }

    // Check if member is admin
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    // Check if member is guest
    public function isGuest()
    {
        return $this->role === self::ROLE_GUEST;
    }
}
