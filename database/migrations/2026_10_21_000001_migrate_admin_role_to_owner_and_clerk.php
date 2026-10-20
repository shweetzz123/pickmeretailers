<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The single flat "admin" role is replaced by a two-tier staff model:
 * "owner" (super user, full access) and "clerk" (restricted staff).
 *
 * Existing admin accounts become owners.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', 'admin')
            ->update(['user_type' => 'owner']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('user_type', ['owner', 'clerk'])
            ->update(['user_type' => 'admin']);
    }
};
