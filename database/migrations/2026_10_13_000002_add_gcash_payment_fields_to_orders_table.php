<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments move from the Maya Checkout gateway to manual GCash QR
     * payments with admin verification. This adds the fields the GCash flow
     * needs and removes the now-unused Maya checkout columns.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('orders', 'payment_proof_path')) {
                $table->string('payment_proof_path')->nullable()->after('payment_reference');
            }
            if (! Schema::hasColumn('orders', 'payment_submitted_at')) {
                $table->timestamp('payment_submitted_at')->nullable()->after('payment_proof_path');
            }
            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'payment_verified_by')) {
                $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'payment_notes')) {
                $table->string('payment_notes')->nullable();
            }
        });

        // SQLite (used by the test suite) refuses to drop a column while an
        // index still references it, so drop the unique indexes left behind by
        // the Maya checkout columns before dropping the columns themselves.
        $existingIndexes = collect(Schema::getIndexes('orders'))->pluck('name')->all();

        foreach ([
            'orders_checkout_session_id_unique',
            'orders_payment_request_reference_unique',
            'orders_payment_intent_id_unique',
        ] as $index) {
            if (in_array($index, $existingIndexes, true)) {
                Schema::table('orders', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index);
                });
            }
        }

        foreach (['checkout_session_url', 'checkout_session_id', 'payment_request_reference', 'payment_intent_id'] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                Schema::table('orders', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
            if (Schema::hasColumn('orders', 'payment_proof_path')) {
                $table->dropColumn('payment_proof_path');
            }
            if (Schema::hasColumn('orders', 'payment_submitted_at')) {
                $table->dropColumn('payment_submitted_at');
            }
            if (Schema::hasColumn('orders', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
            if (Schema::hasColumn('orders', 'payment_notes')) {
                $table->dropColumn('payment_notes');
            }
            if (Schema::hasColumn('orders', 'payment_verified_by')) {
                $table->dropConstrainedForeignId('payment_verified_by');
            }
        });
    }
};
