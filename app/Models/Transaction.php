<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    public const PAYMENT_METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer Bank',
        'ewallet' => 'E-Wallet',
        'card' => 'Kartu Debit/Kredit',
    ];

    public const STATUSES = [
        'paid' => 'Lunas',
        'pending' => 'Menunggu Pembayaran',
        'failed' => 'Gagal',
        'expired' => 'Kedaluwarsa',
    ];

    protected $fillable = [
        'invoice_number',
        'branch_id',
        'user_id',
        'customer_id',
        'shift_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid',
        'change',
        'payment_method',
        'payment_status',
        'payment_reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'float',
            'discount' => 'float',
            'tax' => 'float',
            'total' => 'float',
            'paid' => 'float',
            'change' => 'float',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeBetweenDates(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59']);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->payment_status] ?? ucfirst((string) $this->payment_status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'success',
            'pending' => 'warning',
            'failed', 'expired' => 'danger',
            default => 'gray',
        };
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ymd');
        $last = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        do {
            $number = $prefix.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (static::query()->where('invoice_number', $number)->exists());

        return $number;
    }
}
