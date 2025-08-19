<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'zalo_id',
        'zalo_gid',
        'zalo_name',
        'zalo_avatar',
        'zalo_access_token',
        'zalo_refresh_token',
        'zalo_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'zalo_access_token',
        'zalo_refresh_token',
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
            'zalo_token_expires_at' => 'datetime',
        ];
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

    // Relationship trực tiếp với Attendance
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Relationship trực tiếp với Events
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    // Relationship trực tiếp với FundTransactions
    public function fundTransactions()
    {
        return $this->hasMany(FundTransaction::class);
    }

    // Relationship trực tiếp với GameMatches
    public function gameMatches()
    {
        return $this->hasMany(GameMatch::class);
    }

    // Helper method để kiểm tra user có phải là admin của club không
    public function isClubAdmin(Club $club): bool
    {
        return $this->clubs()
            ->where('club_id', $club->id)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    // Helper method để kiểm tra user có phải là member của club không
    public function isClubMember(Club $club): bool
    {
        return $this->clubs()
            ->where('club_id', $club->id)
            ->wherePivot('is_active', true)
            ->exists();
    }
}
