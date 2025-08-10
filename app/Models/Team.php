<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'name',
        'score',
        'is_winner'
    ];

    protected $casts = [
        'is_winner' => 'boolean',
    ];

    // Relationships
    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'team_players', 'team_id', 'member_id');
    }

    // Scopes
    public function scopeWinners($query)
    {
        return $query->where('is_winner', true);
    }

    public function scopeLosers($query)
    {
        return $query->where('is_winner', false);
    }

    // Accessors
    public function getPlayerCountAttribute()
    {
        return $this->players()->count();
    }

    public function getFormattedScoreAttribute()
    {
        return $this->score ?? 'N/A';
    }
}
