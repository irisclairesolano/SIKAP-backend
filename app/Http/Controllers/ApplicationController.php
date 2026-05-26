<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\JobPost;
use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    protected ApplicationService $applicationService;

    public function __construct(ApplicationService $applicationService)
    {
        $this->applicationService = $applicationService;
    }

    public function myApplications(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can view applications.'], 403);
        }

        $applications = Application::query()->with('job.employer')
            ->where('worker_id', $user->id)
            ->paginate(15);

        return response()->json($applications);
    }

    public function apply(Request $request, int $jobId)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can apply for jobs.'], 403);
        }

        $job = JobPost::query()->whereNull('deleted_at')->findOrFail($jobId);

        $validator = Validator::make($request->all(), [
            'cover_note' => 'nullable|string|max:1000'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $application = $this->applicationService->apply($user, $job, $request->cover_note);

        return response()->json([
            'message' => 'Application submitted.',
            'application_id' => $application->id
        ], 201);
    }

    public function withdraw(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can withdraw applications.'], 403);
        }

        $application = Application::query()->where('worker_id', $user->id)->findOrFail($id);
        $this->applicationService->withdraw($application);

        return response()->json(['message' => 'Application withdrawn.']);
    }

    public function accept(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can accept offers.'], 403);
        }

        $application = Application::query()->where('worker_id', $user->id)->findOrFail($id);
        $this->applicationService->acceptOffer($application);

        return response()->json(['message' => 'Offer accepted.']);
    }

    public function reject(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can reject offers.'], 403);
        }

        $application = Application::query()->where('worker_id', $user->id)->findOrFail($id);
        $this->applicationService->rejectOffer($application);

        return response()->json(['message' => 'Offer rejected.']);
    }

    public function flagOffline(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can flag jobs as complete.'], 403);
        }

        $job = JobPost::query()->whereNull('deleted_at')->findOrFail($id);
        $application = Application::query()->where('worker_id', $user->id)
            ->where('job_post_id', $job->id)
            ->where('status', 'accepted')
            ->firstOrFail();

        $this->applicationService->flagOffline($application);

        return response()->json(['message' => 'Job marked as completed offline.']);
    }

    public function jobApplications(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can view job applications.'], 403);
        }

        $job = JobPost::query()->where('employer_id', $user->id)
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $applications = Application::query()->with('worker.workerProfile.skills', 'worker.workerProfile.experiences', 'worker.workerProfile.references')
            ->where('job_post_id', $job->id)
            ->get();

        return response()->json(ApplicationResource::collection($applications));
    }

    public function jobRequest(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can send job requests.'], 403);
        }

        $application = Application::query()->whereHas('job', function ($query) use ($user) {
            $query->where('employer_id', $user->id);
        })->findOrFail($id);

        $this->applicationService->jobRequest($application);

        return response()->json(['message' => 'Job request sent.']);
    }

    public function confirmHire(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can confirm hires.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $application = Application::query()->whereHas('job', function ($query) use ($user) {
            $query->where('employer_id', $user->id);
        })->findOrFail($id);

        $this->applicationService->confirmHire($application, $request->price);

        return response()->json(['message' => 'Hire confirmed.']);
    }

    public function cancelHire(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can cancel hires.'], 403);
        }

        $application = Application::query()->whereHas('job', function ($query) use ($user) {
            $query->where('employer_id', $user->id);
        })->findOrFail($id);

        $this->applicationService->cancelHire($application);

        return response()->json(['message' => 'Hire cancelled.']);
    }

    public function getContact(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Only employers can view contact information.'], 403);
        }

        $application = Application::query()->whereHas('job', function ($query) use ($user) {
            $query->where('employer_id', $user->id);
        })->findOrFail($id);

        if (!$application->contact_revealed) {
            return response()->json(['message' => 'Contact not yet revealed.'], 403);
        }

        return response()->json(['phone' => $application->worker->phone]);
    }
}
