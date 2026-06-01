<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureRegistrationStatus
{
    public function handle(Request $request, Closure $next)
    {
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

        if ($status === 'pending_id_upload' && $path === $allowedUploadPath) {
            return $next($request);
        }

        if ($path === $allowedLogoutPath) {
            return $next($request);
        }

        if ($status === 'pending_email_verification') {
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
