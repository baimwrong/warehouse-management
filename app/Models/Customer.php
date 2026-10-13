<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'shipping_address',
        'billing_address',
        'city',
        'country',
        'status',
    ];

    public function outboundOrders(): HasMany
    {
        return $this->hasMany(OutboundOrder::class);
    }
}
