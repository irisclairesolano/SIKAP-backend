<?php

namespace App\Http\Controllers;

use App\Models\JobPost;
use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    protected $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function index(Request $request)
    {
        $query = JobPost::with('employer')
            ->whereNull('deleted_at')
            ->where('status', $request->get('status', 'open'));

        // Filters
        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->barangay) {
            $query->where('barangay', $request->barangay);
        }
        if ($request->municipality) {
            $query->where('municipality', $request->municipality);
        }
        if ($request->search) {
            $query->where('title', 'ILIKE', '%' . $request->search . '%');
        }

        $jobs = $query->paginate(15);

        return response()->json($jobs);
    }

    public function show(Request $request, $id)
    {
        $job = JobPost::with('employer.employerProfile')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        return response()->json($job);
    }

    public function myJobs(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'employer') {
            $jobs = JobPost::with('applications')
                ->where('employer_id', $user->id)
                ->whereNull('deleted_at')
                ->paginate(15);
        } elseif ($user->role === 'worker') {
            $jobs = JobPost::whereHas('applications', function ($query) use ($user) {
                $query->where('worker_id', $user->id);
            })
            ->with(['applications' => function ($query) use ($user) {
                $query->where('worker_id', $user->id);
            }])
            ->whereNull('deleted_at')
            ->paginate(15);
        } else {
            return response()->json(['message' => 'Invalid role.'], 403);
        }

        return response()->json($jobs);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can create jobs.'], 403);
        }

        // Guard: Employer may not have more than 5 active jobs
        $activeJobsCount = JobPost::where('employer_id', $user->id)
            ->whereIn('status', ['open', 'closed_in_progress'])
            ->whereNull('deleted_at')
            ->count();

        if ($activeJobsCount >= 5) {
            return response()->json(['message' => 'Maximum active jobs limit reached.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'barangay' => 'required|string',
            'municipality' => 'required|string',
            'duration_type' => 'nullable|string',
            'compensation' => 'nullable|numeric|min:0',
            'slots' => 'required|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $job = JobPost::create(array_merge(
            $request->all(),
            ['employer_id' => $user->id, 'status' => 'open']
        ));

        return response()->json(['message' => 'Job created successfully.', 'job' => $job], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can update jobs.'], 403);
        }

        $job = JobPost::where('employer_id', $user->id)
            ->where('status', 'open')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'barangay' => 'nullable|string',
            'municipality' => 'nullable|string',
            'duration_type' => 'nullable|string',
            'compensation' => 'nullable|numeric|min:0',
            'slots' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $job->update($request->all());

        return response()->json(['message' => 'Job updated successfully.', 'job' => $job]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can delete jobs.'], 403);
        }

        $job = JobPost::where('employer_id', $user->id)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        // Guard: job must have no accepted/confirmed applications
        if ($job->applications()->whereIn('status', ['accepted', 'employer_confirmed'])->exists()) {
            return response()->json(['message' => 'Cannot delete job with accepted applications.'], 422);
        }

        $job->delete();

        return response()->json(['message' => 'Job deleted successfully.']);
    }

    public function markComplete(Request $request, $id)
    {
        $user = $request->user();
        
        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can mark jobs complete.'], 403);
        }

        $job = JobPost::where('employer_id', $user->id)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $this->applicationService->markJobComplete($job);

        return response()->json(['message' => 'Job marked as complete.']);
    }
}
