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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'push_notifications')) {
                $table->boolean('push_notifications')->default(false)->after('is_suspended');
            }
            if (!Schema::hasColumn('users', 'location_services')) {
                $table->boolean('location_services')->default(false)->after('push_notifications');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'location_services')) {
                $table->dropColumn('location_services');
            }
            if (Schema::hasColumn('users', 'push_notifications')) {
                $table->dropColumn('push_notifications');
            }
        });
    }
};
