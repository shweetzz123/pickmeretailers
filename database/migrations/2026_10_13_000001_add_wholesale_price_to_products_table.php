<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reseller wholesale price. When set, retailers (the default shopper) pay
     * product_price while resellers pay wholesale_price. Null falls back to
     * the retail price for everyone.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 10, 2)->nullable()->after('product_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'wholesale_price')) {
                $table->dropColumn('wholesale_price');
            }
        });
    }
};
