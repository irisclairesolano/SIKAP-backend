<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SemaphoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminVerificationController extends Controller
{
    protected SemaphoreService $semaphoreService;

    public function __construct(SemaphoreService $semaphoreService)
    {
        $this->semaphoreService = $semaphoreService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $status = $request->get('status', 'pending');

        $users = User::query()->where('role', '!=', 'admin')
            ->where('verification_status', $status)
            ->whereNotNull('document_url')
            ->paginate(15);

        return response()->json($users);
    }

    public function verify(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,correction_needed'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $targetUser = User::findOrFail($id);

        $targetUser->update(['verification_status' => $request->status]);

        if ($request->status === 'approved') {
            $targetUser->update(['verification_badge' => true]);
            $message = "SIKAP: Your account has been approved! You can now use the platform.";
        } elseif ($request->status === 'rejected') {
            $message = "SIKAP: Your ID submission was rejected. Please upload a clearer copy.";
        } else { // correction_needed
            $message = "SIKAP: Your ID needs correction. Please re-upload in the app.";
        }

        // SMS to user
        $this->semaphoreService->send((string)$targetUser->phone, (string)$message, null);

        return response()->json(['message' => 'User verification status updated.']);
    }
}
