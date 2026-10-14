<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collapse the old retailer/reseller account roles into a single "user"
     * account type. Pricing is now quantity-based, so the role distinction is
     * no longer used. Administrators are preserved.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', '!=', 'admin')
            ->update(['user_type' => 'user']);

        // Refresh the bulk-pricing hero copy seeded before pricing became
        // quantity-based.
        if (Schema::hasTable('hero_slides')) {
            DB::table('hero_slides')
                ->where('subheading', 'like', '%Resale-ready pricing%')
                ->update(['subheading' => 'Buy in bulk and unlock resale pricing automatically — no approval needed.']);
        }
    }

    /**
     * Irreversible: the historical retailer/reseller split is not restored.
     */
    public function down(): void
    {
        //
    }
};
