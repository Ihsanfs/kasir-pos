<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'total_sales',
        'total_cash_sales',
        'total_transactions',
        'difference',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'float',
            'closing_cash' => 'float',
            'expected_cash' => 'float',
            'total_sales' => 'float',
            'total_cash_sales' => 'float',
            'total_transactions' => 'integer',
            'difference' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'open' ? 'Terbuka' : 'Ditutup';
    }

    public function calculateExpectedCash(): float
    {
        $cash = $this->transactions()
            ->where('payment_status', 'paid')
            ->where('payment_method', 'cash')
            ->sum('total');

        return (float) $this->opening_cash + (float) $cash;
    }
}
