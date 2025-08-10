<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserClub extends Model
{
    use HasFactory;

    protected $table = 'user_clubs';

    protected $fillable = [
        'user_id',
        'club_id',
        'role',
        'joined_date',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'joined_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationship với User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship với Club
    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
