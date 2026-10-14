<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maya Checkout has been removed in favour of manual GCash payments, so
     * the webhook event log is no longer needed.
     */
    public function up(): void
    {
        Schema::dropIfExists('maya_webhook_events');
    }

    public function down(): void
    {
        // Intentionally not recreated: the Maya integration no longer exists.
    }
};
