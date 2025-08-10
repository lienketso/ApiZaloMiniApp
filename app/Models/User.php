<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'join_date',
        'zalo_gid', // Thêm field này
        'zalo_name',
        'zalo_avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'join_date' => 'date',
        ];
    }

    // Relationship với Member (nếu cần)
    public function member()
    {
        return $this->hasOne(Member::class);
    }

    // Relationship với Clubs thông qua user_clubs
    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'user_clubs')
                    ->withPivot('role', 'joined_date', 'notes', 'is_active')
                    ->withTimestamps();
    }

    // Relationship với user_clubs pivot table
    public function userClubs()
    {
        return $this->hasMany(UserClub::class);
    }

    // Relationship với Attendance
    public function attendances()
    {
//        return $this->hasMany(Attendance::class, 'member_id');
    }

}
