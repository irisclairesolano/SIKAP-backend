<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Check registration status for a given email.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Attempt to find the user by email
        $user = \App\Models\User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Use model helper to determine registration status if not set
        $status = $user->registration_status ?? $user->getRegistrationStatus();

        return response()->json([
            'status' => $status,
            'user' => $user,
        ]);
    }

    //
}
