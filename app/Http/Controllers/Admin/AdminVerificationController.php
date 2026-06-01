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

        $status = $request->get('status', 'pending_review');

        $users = User::query()->where('role', '!=', 'admin')
            ->where(function ($query) use ($status) {
                $query->where('registration_status', $status);

                if ($status === 'approved') {
                    $query->orWhere(function ($sub) {
                        $sub->whereNull('registration_status')
                            ->where('verification_status', 'approved');
                    });
                }

                if ($status === 'pending_review') {
                    $query->orWhere(function ($sub) {
                        $sub->whereNull('registration_status')
                            ->where('verification_status', 'pending')
                            ->whereNotNull('document_url');
                    });
                }
            })
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

        $registrationStatus = $request->status === 'approved'
            ? 'approved'
            : ($request->status === 'rejected' ? 'rejected' : 'pending_id_upload');

        $verificationStatus = $request->status === 'approved' ? 'approved' : ($request->status === 'rejected' ? 'rejected' : 'pending');

        $targetUser->update([
            'verification_status' => $verificationStatus,
            'registration_status' => $registrationStatus,
            'verification_badge' => $request->status === 'approved'
        ]);

        if ($request->status === 'approved') {
            $message = "SIKAP: Your account has been approved! You can now use the platform.";
        } elseif ($request->status === 'rejected') {
            $message = "SIKAP: Your ID submission was rejected. Please upload a clearer copy.";
        } else {
            $message = "SIKAP: Your ID needs correction. Please re-upload in the app.";
        }

        $this->semaphoreService->send((string)$targetUser->phone, (string)$message, null);

        return response()->json(['message' => 'User verification status updated.']);
    }
}
