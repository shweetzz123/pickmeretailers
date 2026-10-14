<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The shop serves retailers and resellers. Existing customer accounts
     * (user_type = "user") become retailers; the reseller role is opt-in at
     * signup or assigned by an administrator.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', 'user')
            ->update(['user_type' => 'retailer']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('user_type', 'retailer')
            ->update(['user_type' => 'user']);
    }
};
