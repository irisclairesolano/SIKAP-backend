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
        if (!Schema::hasTable('worker_skill')) {
            Schema::create('worker_skill', function (Blueprint $table) {
                $table->id();
                $table->foreignId('worker_profile_id')->constrained('worker_profiles')->onDelete('cascade');
                $table->foreignId('skill_id')->constrained('skills')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_skill');
    }
};
