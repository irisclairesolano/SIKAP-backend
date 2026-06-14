<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EnsureRegistrationStatus
{
    public function handle(Request $request, Closure $next)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if ($user->is_suspended) {
            abort(403, 'Account suspended.');
        }

        $status = $user->getRegistrationStatus();
        if ($status === 'approved') {
            return $next($request);
        }

        $path = $request->path();
        $allowedUploadPath = 'v1/auth/upload-id';
        $allowedLogoutPath = 'v1/auth/logout';
        $allowedEmailUpdatePath = 'api/v1/auth/email';
        $allowedEmailUpdatePathAlt = 'v1/auth/email';

        $isGetProfile = $request->isMethod('get') && ($path === 'v1/profile' || $path === 'api/v1/profile');

        if ($isGetProfile) {
            return $next($request);
        }

        if ($status === 'pending_id_upload' && ($path === $allowedUploadPath || $path === 'api/' . $allowedUploadPath)) {
            return $next($request);
        }

        if ($path === $allowedLogoutPath || $path === 'api/' . $allowedLogoutPath) {
            return $next($request);
        }

        if ($status === 'pending_email_verification') {
            if ($path === $allowedEmailUpdatePath || $path === $allowedEmailUpdatePathAlt) {
                return $next($request);
            }
            return response()->json([
                'message' => 'Please verify your email to continue registration.',
                'registration_status' => $status,
                'next_step' => 'verify_email'
            ], 403);
        }

        if ($status === 'pending_id_upload') {
            return response()->json([
                'message' => 'Please upload your government ID to complete registration.',
                'registration_status' => $status,
                'next_step' => 'upload_id'
            ], 403);
        }

        if ($status === 'pending_review') {
            return response()->json([
                'message' => 'Your account is awaiting approval.',
                'registration_status' => $status
            ], 403);
        }

        if ($status === 'rejected') {
            return response()->json([
                'message' => 'Your registration was rejected. Please contact support or try again with a different email.',
                'registration_status' => $status
            ], 403);
        }

        return response()->json([
            'message' => 'Registration incomplete. Please continue the registration process.',
            'registration_status' => $status
        ], 403);
    }
}
