<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\AdminVerificationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminJobController;
use App\Http\Controllers\LocationController;

Route::prefix('v1')->group(function () {

    // Public
    Route::post('auth/register',   [AuthController::class, 'register']);
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('auth/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('auth/login',      [AuthController::class, 'login']);

    // Location dropdowns — public, no auth required
    Route::prefix('locations')->group(function () {
        Route::get('municipalities', [LocationController::class, 'municipalities']);
        Route::get('barangays', [LocationController::class, 'barangays']);
    });

    // Authenticated
    Route::middleware(['auth:sanctum', 'registration_status'])->group(function () {

        Route::post('auth/upload-id', [AuthController::class, 'uploadId']);
        Route::post('auth/logout',    [AuthController::class, 'logout']);

        // Profile
        Route::get   ('profile',                    [ProfileController::class, 'show']);
        Route::put   ('profile',                    [ProfileController::class, 'update']);
        Route::post  ('profile/skills',             [ProfileController::class, 'syncSkills']);
        Route::post  ('profile/experiences',        [ProfileController::class, 'addExperience']);
        Route::delete('profile/experiences/{id}',   [ProfileController::class, 'removeExperience']);
        Route::post  ('profile/references',         [ProfileController::class, 'addReference']);
        Route::delete('profile/references/{id}',    [ProfileController::class, 'removeReference']);

        // Jobs (public read)
        Route::get('jobs',     [JobController::class, 'index']);
        Route::get('jobs/{id}',[JobController::class, 'show']);
        Route::get('my-jobs',  [JobController::class, 'myJobs']);

        // Applications (read)
        Route::get('my-applications', [ApplicationController::class, 'myApplications']);

        // Employer only
        Route::middleware('role:employer')->group(function () {
            Route::post  ('jobs',                               [JobController::class, 'store']);
            Route::patch ('jobs/{id}',                         [JobController::class, 'update']);
            Route::delete('jobs/{id}',                         [JobController::class, 'destroy']);
            Route::patch ('jobs/{id}/complete',                [JobController::class, 'markComplete']);
            Route::get   ('jobs/{id}/applications',            [ApplicationController::class, 'jobApplications']);
            Route::patch ('applications/{id}/job-request',     [ApplicationController::class, 'jobRequest']);
            Route::patch ('applications/{id}/confirm',         [ApplicationController::class, 'confirmHire']);
            Route::patch ('applications/{id}/cancel-hire',     [ApplicationController::class, 'cancelHire']);
            Route::get   ('applications/{id}/contact',         [ApplicationController::class, 'getContact']);
        });

        // Worker only
        Route::middleware('role:worker')->group(function () {
            Route::post  ('jobs/{id}/apply',         [ApplicationController::class, 'apply']);
            Route::delete('applications/{id}',       [ApplicationController::class, 'withdraw']);
            Route::patch ('applications/{id}/accept',[ApplicationController::class, 'accept']);
            Route::patch ('applications/{id}/reject',[ApplicationController::class, 'reject']);
            Route::post  ('jobs/{id}/flag-offline',  [ApplicationController::class, 'flagOffline']);
        });

        // Reviews & Reports (any authenticated role)
        Route::post('applications/{id}/review', [ReviewController::class, 'store']);
        Route::post('reports',                  [ReportController::class, 'store']);

        // Admin only
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get   ('verifications',         [AdminVerificationController::class, 'index']);
            Route::patch ('users/{id}/verify',     [AdminVerificationController::class, 'verify']);
            Route::get   ('users',                 [AdminUserController::class, 'index']);
            Route::patch ('users/{id}',            [AdminUserController::class, 'update']);
            Route::get   ('reports',               [AdminReportController::class, 'index']);
            Route::patch ('reports/{id}',          [AdminReportController::class, 'resolve']);
            Route::patch ('jobs/{id}/mark-complete',[AdminJobController::class, 'markComplete']);
            Route::get   ('analytics',             [AdminAnalyticsController::class, 'index']);
        });
    });
});
