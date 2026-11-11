<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'unit',
        'price',
        'cost_price',
        'qty',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'cost_price' => 'float',
            'qty' => 'integer',
            'subtotal' => 'float',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
