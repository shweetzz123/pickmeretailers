<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the Lalamove courier integration columns from orders.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'lalamove_order_id',
                'lalamove_status',
                'delivery_lat',
                'delivery_lng',
                'quotation_id',
                'pickup_stop_id',
                'delivery_stop_id',
                'delivery_status',
                'tracking_url',
                'pickup_address',
                'pickup_lat',
                'pickup_lng',
                'last_lalamove_webhook_at',
                'lalamove_driver_name',
                'lalamove_driver_phone',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('lalamove_order_id')->nullable();
            $table->string('lalamove_status')->nullable();
            $table->decimal('delivery_lat', 10, 7)->nullable();
            $table->decimal('delivery_lng', 10, 7)->nullable();
            $table->string('quotation_id')->nullable();
            $table->string('pickup_stop_id')->nullable();
            $table->string('delivery_stop_id')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->string('tracking_url')->nullable();
            $table->string('pickup_address')->nullable();
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->timestamp('last_lalamove_webhook_at')->nullable();
            $table->string('lalamove_driver_name')->nullable();
            $table->string('lalamove_driver_phone')->nullable();
        });
    }
};
