<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Email and user_id nullable columns are now created in 2026_05_03_100000_create_email_otps_table
        // This migration is kept for historical reference but does nothing
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op since this migration does nothing
    }
};
