<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Review;
use App\Services\ReputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    protected $reputationService;

    public function __construct(ReputationService $reputationService)
    {
        $this->reputationService = $reputationService;
    }

    public function store(Request $request, $id)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'cat1' => 'required|integer|between:1,5',
            'cat2' => 'required|integer|between:1,5',
            'cat3' => 'required|integer|between:1,5',
            'cat4' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $application = Application::findOrFail($id);

        // Verify app->status === 'completed'
        if ($application->status !== 'completed') {
            return response()->json(['message' => 'Job must be completed before reviewing.'], 422);
        }

        // Verify job rating_window_expires_at > now()
        if ($application->job->rating_window_expires_at <= now()) {
            return response()->json(['message' => 'Rating window has closed.'], 422);
        }

        // Determine reviewer_role and reviewee_id based on requesting user's role
        if ($user->role === 'worker' && $application->worker_id === $user->id) {
            $reviewerRole = 'worker';
            $revieweeId = $application->job->employer_id;
        } elseif ($user->role === 'employer' && $application->job->employer_id === $user->id) {
            $reviewerRole = 'employer';
            $revieweeId = $application->worker_id;
        } else {
            return response()->json(['message' => 'Unauthorized to review this application.'], 403);
        }

        // Check for duplicate review (UNIQUE constraint)
        try {
            $overallRating = round(($request->cat1 + $request->cat2 + $request->cat3 + $request->cat4) / 4, 2);

            $review = Review::create([
                'application_id' => $application->id,
                'reviewer_id' => $user->id,
                'reviewee_id' => $revieweeId,
                'reviewer_role' => $reviewerRole,
                'cat1' => $request->cat1,
                'cat2' => $request->cat2,
                'cat3' => $request->cat3,
                'cat4' => $request->cat4,
                'overall_rating' => $overallRating,
                'comment' => $request->comment
            ]);

            // Recalculate reputation
            $reviewee = \App\Models\User::find($revieweeId);
            $this->reputationService->recalculate($reviewee);

            return response()->json(['message' => 'Review submitted.'], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505') { // Unique constraint violation
                return response()->json(['message' => 'You have already reviewed this job.'], 422);
            }
            throw $e;
        }
    }
}
