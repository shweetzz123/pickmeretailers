<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transaction ledger. Every verified payment (and any manual adjustment or
     * refund) is recorded here so the shop owner can monitor money movement
     * independently of order state.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('payment');       // payment, refund, adjustment
            $table->string('method')->default('gcash');       // gcash, cash, bank
            $table->string('status')->default('completed');   // completed, pending, failed
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
