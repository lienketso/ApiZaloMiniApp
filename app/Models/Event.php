<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Member;
class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'max_participants',
        'current_participants',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
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

    public function members()
    {
        return $this->belongsToMany(Member::class, 'attendance');
    }

    // Scopes
    public function scopeByClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('end_date', '<', now());
    }

    // Accessors
    public function getAttendanceStatsAttribute()
    {
        $total = $this->attendances()->count();
        $present = $this->attendances()->where('status', 'present')->count();
        $absent = $this->attendances()->where('status', 'absent')->count();
        $late = $this->attendances()->where('status', 'late')->count();

        return [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'present_rate' => $total > 0 ? round(($present / $total) * 100, 2) : 0
        ];
    }


}
