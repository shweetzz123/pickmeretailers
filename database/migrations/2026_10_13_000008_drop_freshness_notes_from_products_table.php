<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The per-product "Freshness Notes" feature (Storage, Prep, Delivery,
     * Best For) has been removed, so drop the columns it used.
     */
    public function up(): void
    {
        $columns = array_values(array_filter(
            ['fresh_storage', 'fresh_prep', 'fresh_delivery', 'fresh_best_for'],
            fn (string $column) => Schema::hasColumn('products', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'fresh_storage')) {
                $table->string('fresh_storage')->nullable()->after('product_type');
            }
            if (! Schema::hasColumn('products', 'fresh_prep')) {
                $table->string('fresh_prep')->nullable()->after('fresh_storage');
            }
            if (! Schema::hasColumn('products', 'fresh_delivery')) {
                $table->string('fresh_delivery')->nullable()->after('fresh_prep');
            }
            if (! Schema::hasColumn('products', 'fresh_best_for')) {
                $table->string('fresh_best_for')->nullable()->after('fresh_delivery');
            }
        });
    }
};
