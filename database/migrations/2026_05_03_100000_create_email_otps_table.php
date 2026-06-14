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
        if (!Schema::hasTable('email_otps')) {
            Schema::create('email_otps', function (Blueprint $table) {
                $table->id();
                $table->string('email')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('otp_hash');
                $table->timestamp('expires_at')->nullable();
                $table->index('email');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('email_otps')) {
            Schema::dropIfExists('email_otps');
        }
    }
};
