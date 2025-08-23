<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'phone',
        'invite_token',
        'invited_by',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // Relationship với Club
    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    // Relationship với User (người mời)
    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    // Tạo token mới
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    // Kiểm tra token có hết hạn không
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    // Kiểm tra có thể sử dụng không
    public function canBeUsed(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    // Đánh dấu đã sử dụng
    public function markAsAccepted(): void
    {
        $this->update(['status' => 'accepted']);
    }

    // Đánh dấu hết hạn
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    // Boot method để tự động tạo token và set thời gian hết hạn
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->invite_token)) {
                $invitation->invite_token = self::generateToken();
            }
            
            if (empty($invitation->expires_at)) {
                // Token hết hạn sau 7 ngày
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }
}
