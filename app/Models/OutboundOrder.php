<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboundOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'warehouse_id',
        'status',
        'priority',
        'shipping_carrier',
        'tracking_number',
        'scheduled_ship_date',
        'shipped_date',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'scheduled_ship_date' => 'date',
        'shipped_date' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutboundOrderItem::class);
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->items()->sum('quantity_ordered');
        if ($total <= 0) {
            return 0;
        }

        if ($this->status === 'shipped' || $this->status === 'delivered') {
            return 100;
        }

        $packed = $this->items()->sum('quantity_packed');
        return min(100, (int) round(($packed / $total) * 100));
    }
}
