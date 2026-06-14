<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('worker')->after('email');
                $table->string('phone')->nullable()->after('role');
                $table->string('barangay')->nullable()->after('phone');
                $table->string('municipality')->nullable()->after('barangay');
                $table->string('document_url')->nullable()->after('municipality');
                $table->string('verification_status')->default('unverified')->after('document_url');
                $table->boolean('verification_badge')->default(false)->after('verification_status');
                $table->boolean('is_suspended')->default(false)->after('verification_badge');
                $table->decimal('reputation_score', 8, 2)->default(0)->after('is_suspended');
            }
        });

        if (!Schema::hasTable('municipalities')) {
            Schema::create('municipalities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('barangays')) {
            Schema::create('barangays', function (Blueprint $table) {
                $table->id();
                $table->foreignId('municipality_id')->constrained('municipalities')->onDelete('cascade');
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('employer_profiles')) {
            Schema::create('employer_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('description')->nullable();
                $table->string('contact_info')->nullable();
                $table->decimal('reputation_score', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('worker_profiles')) {
            Schema::create('worker_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('bio')->nullable();
                $table->string('availability_status')->default('available');
                $table->decimal('reputation_score', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('worker_experiences')) {
            Schema::create('worker_experiences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('worker_profile_id')->constrained('worker_profiles')->onDelete('cascade');
                $table->string('job_title');
                $table->string('employer_name');
                $table->string('duration');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('worker_character_references')) {
            Schema::create('worker_character_references', function (Blueprint $table) {
                $table->id();
                $table->foreignId('worker_profile_id')->constrained('worker_profiles')->onDelete('cascade');
                $table->string('name');
                $table->string('phone');
                $table->string('relationship');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('job_posts')) {
            Schema::create('job_posts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_id')->constrained('users')->onDelete('cascade');
                $table->string('reference_number')->unique();
                $table->string('title');
                $table->text('description');
                $table->string('category');
                $table->string('barangay')->nullable();
                $table->string('municipality')->nullable();
                $table->string('duration_type');
                $table->decimal('compensation', 10, 2);
                $table->integer('slots')->default(1);
                $table->integer('accepted_count')->default(0);
                $table->string('status')->default('open');
                $table->timestamp('rating_window_expires_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('applications')) {
            Schema::create('applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_post_id')->constrained('job_posts')->onDelete('cascade');
                $table->foreignId('worker_id')->constrained('users')->onDelete('cascade');
                $table->text('cover_note')->nullable();
                $table->string('status')->default('pending');
                $table->boolean('references_revealed')->default(false);
                $table->boolean('contact_revealed')->default(false);
                $table->timestamp('slot_locked_at')->nullable();
                $table->timestamp('employer_confirmed_at')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->decimal('final_agreed_price', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
                $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade');
                $table->string('reviewer_role');
                $table->integer('cat1')->default(0);
                $table->integer('cat2')->default(0);
                $table->integer('cat3')->default(0);
                $table->integer('cat4')->default(0);
                $table->decimal('overall_rating', 3, 2)->default(0);
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
                $table->string('reportable_type');
                $table->unsignedBigInteger('reportable_id');
                $table->string('type');
                $table->text('description');
                $table->string('status')->default('pending');
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('new_user_id')->constrained('users')->onDelete('cascade');
                $table->string('referrer_contact');
                $table->string('referrer_name')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('job_posts');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('worker_character_references');
        Schema::dropIfExists('worker_experiences');
        Schema::dropIfExists('worker_profiles');
        Schema::dropIfExists('employer_profiles');
        Schema::dropIfExists('barangays');
        Schema::dropIfExists('municipalities');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'phone', 'barangay', 'municipality', 'document_url',
                'verification_status', 'verification_badge', 'is_suspended', 'reputation_score'
            ]);
        });
    }
};
