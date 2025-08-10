<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'club_id',
        'title',
        'description',
        'match_date',
        'time',
        'location',
        'status',
        'bet_amount',
        'created_by'
    ];

    protected $casts = [
        'match_date' => 'date',
        'bet_amount' => 'decimal:2',
    ];

    // Relationships
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'match_id');
    }

    public function teamA()
    {
        return $this->hasOne(Team::class, 'match_id')->where('name', 'like', '%A%');
    }

    public function teamB()
    {
        return $this->hasOne(Team::class, 'match_id')->where('name', 'like', '%B%');
    }

    // Scopes
    public function scopeByClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getFormattedBetAmountAttribute()
    {
        return number_format($this->bet_amount, 0, ',', '.') . ' VNĐ';
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'upcoming' => 'Sắp diễn ra',
            'ongoing' => 'Đang diễn ra',
            'completed' => 'Đã hoàn thành',
            default => 'Không xác định'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'upcoming' => 'blue',
            'ongoing' => 'green',
            'completed' => 'purple',
            default => 'gray'
        };
    }
}
