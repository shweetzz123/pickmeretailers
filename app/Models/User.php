<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * user_type is intentionally NOT fillable: roles must only be assigned
     * through explicit owner-controlled code paths (e.g. AdminUserController).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_code',
        'verification_code_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function generateVerificationCode(): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    public function verifyCode(string $code): bool
    {
        if (
            $this->verification_code === $code &&
            $this->verification_code_expires_at &&
            $this->verification_code_expires_at->isFuture()
        ) {
            $this->update([
                'verification_code' => null,
                'verification_code_expires_at' => null,
            ]);

            return true;
        }

        return false;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function wishlistedItems()
    {
        return $this->hasMany(WishlistedItem::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * The store owner: the super user with full, unrestricted access.
     */
    public function isOwner(): bool
    {
        return $this->user_type === 'owner';
    }

    /**
     * A clerk: staff member restricted to adding products and restocking.
     */
    public function isClerk(): bool
    {
        return $this->user_type === 'clerk';
    }

    /**
     * Anyone with access to the admin panel (owner or clerk).
     */
    public function isStaff(): bool
    {
        return $this->isOwner() || $this->isClerk();
    }

    /**
     * Legacy alias for isStaff() — kept for older call sites.
     */
    public function isAdmin(): bool
    {
        return $this->isStaff();
    }

    /**
     * Anyone who can shop. Every non-staff account is a customer.
     */
    public function isCustomer(): bool
    {
        return ! $this->isStaff();
    }

    /**
     * Accounts that are neither owners nor clerks.
     */
    public function scopeCustomers($query)
    {
        return $query->whereNotIn('user_type', ['owner', 'clerk']);
    }

    /**
     * All staff accounts, grouped by role count.
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('user_type', ['owner', 'clerk']);
    }
}
