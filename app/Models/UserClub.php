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
        'status',
        'joined_date',
        'notes',
        'is_active',
        'approved_at',
        'approved_by',
        'rejection_reason'
    ];

    protected $casts = [
        'joined_date' => 'date',
        'approved_at' => 'datetime',
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

    // Relationship với Admin đã duyệt
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Helper methods để kiểm tra trạng thái
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    // Method để admin duyệt thành viên
    public function approve(int $adminId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $adminId,
            'notes' => $notes
        ]);
    }

    // Method để admin từ chối thành viên
    public function reject(int $adminId, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $adminId
        ]);
    }

    // Method để kích hoạt thành viên
    public function activate(): bool
    {
        return $this->update([
            'status' => 'active',
            'is_active' => true
        ]);
    }

    // Method để vô hiệu hóa thành viên
    public function deactivate(): bool
    {
        return $this->update([
            'status' => 'inactive',
            'is_active' => false
        ]);
    }
}
