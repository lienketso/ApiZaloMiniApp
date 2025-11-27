<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class FundTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'match_id',
        'user_id',
        'type',
        'amount',
        'description',
        'category',
        'transaction_date',
        'notes',
        'created_by',
        'status'
    ];

    protected $appends = ['payment_proof_url'];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->select(['id', 'name', 'email']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name', 'email']);
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(FundTransactionPaymentProof::class)->orderByDesc('created_at');
    }

    // Scopes
    public function scopeByClub($query, $clubId)
    {
        // Filter theo club_id, bao gồm cả trường hợp club_id = NULL (cập nhật sau)
        return $query->where(function($q) use ($clubId) {
            $q->where('club_id', $clubId)
              ->orWhereNull('club_id'); // Bao gồm transactions cũ chưa có club_id
        });
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeByMonth($query, $month, $year)
    {
        return $query->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.') . ' VNĐ';
    }

    public function getTypeLabelAttribute()
    {
        return $this->type === 'income' ? 'Thu' : 'Chi';
    }

    public function getPaymentProofUrlAttribute(): ?string
    {
        $proof = $this->relationLoaded('paymentProofs')
            ? $this->paymentProofs->first()
            : $this->paymentProofs()->first();

        if (!$proof || empty($proof->file_path)) {
            return null;
        }

        try {
            return Storage::disk('public')->url($proof->file_path);
        } catch (\Throwable $th) {
            \Log::warning('Unable to generate payment proof URL', [
                'transaction_id' => $this->id,
                'error' => $th->getMessage(),
            ]);
            return null;
        }
    }
}
