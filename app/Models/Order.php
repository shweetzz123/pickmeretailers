<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'barangay',
        'region',
        'city',
        'phone',
        'notes',
        'idempotency_key',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_proof_path',
        'payment_submitted_at',
        'paid_at',
        'payment_verified_by',
        'payment_notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'payment_submitted_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentVerifier()
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isAwaitingVerification(): bool
    {
        return $this->payment_status === 'awaiting_verification';
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Paid',
            'awaiting_verification' => 'Awaiting verification',
            'rejected' => 'Payment rejected',
            'failed' => 'Failed',
            default => 'Unpaid',
        };
    }

    public function subtotal(): float
    {
        return round($this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        }), 2);
    }

}

