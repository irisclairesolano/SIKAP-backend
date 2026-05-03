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
        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('email')->nullable()->after('id');
            $table->index('email');
            // Make user_id nullable to support pending registrations
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn('email');
            // Revert user_id to not nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
