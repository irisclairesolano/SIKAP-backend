<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPost;
use App\Services\ApplicationService;
use Illuminate\Http\Request;

class AdminJobController extends Controller
{
    protected $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function markComplete(Request $request, $id)
    {
        $user = $request->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $job = JobPost::findOrFail($id);
        $this->applicationService->markJobComplete($job);

        return response()->json(['message' => 'Job marked as complete.']);
    }
}
