<?php

namespace App\Services;

use App\Models\Application;
use App\Models\JobPost;
use App\Models\User;
use App\Jobs\SendHireReminderJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SemaphoreService;
class ApplicationService
{
    protected SemaphoreService $semaphoreService;

    public function __construct(SemaphoreService $semaphoreService)
    {
        $this->semaphoreService = $semaphoreService;
    }

    public function apply(User $worker, JobPost $job, ?string $coverNote): Application
    {
        // Guard: job->status === 'open'
        if ($job->status !== 'open') {
            abort(422, 'Job is no longer accepting applications.', []);
        }

        // Guard: applications count < 50
        if ($job->applications()->count() >= 50) {
            abort(422, 'Application limit reached.', []);
        }

        // Guard: no duplicate application
        if ($job->applications()->where('worker_id', $worker->id)->exists()) {
            abort(422, 'Already applied to this job.', []);
        }

        return Application::query()->create([
            'job_post_id' => $job->id,
            'worker_id' => $worker->id,
            'cover_note' => $coverNote,
            'status' => 'pending',
            'applied_at' => now()
        ]);
    }

    public function sendJobRequest(Application $app): void
    {
        // Guard: app->status === 'pending'
        if ($app->status !== 'pending') {
            abort(422, 'Invalid application status for job request.', []);
        }

        $app->update([
            'status' => 'pending_negotiation',
            'references_revealed' => true
        ]);

        // SMS to worker: Stage 2 template
        $message = "SIKAP: {$app->job->employer->name} has sent you a job request for [{$app->job->title}]. Open the app to review.";
        $this->semaphoreService->send((string)$app->worker->phone, (string)$message, null);
    }

    public function confirmHire(Application $app, float $price): void
    {
        // Guard: app->status === 'pending_negotiation'
        if ($app->status !== 'pending_negotiation') {
            abort(422, 'Invalid application status for hire confirmation.', []);
        }

        DB::transaction(function () use ($app, $price) {
            // Lock the job for update
            $job = JobPost::query()->lockForUpdate()->findOrFail($app->job_post_id);

            // Check job->accepted_count < job->slots
            if ($job->accepted_count >= $job->slots) {
                abort(422, 'All slots are currently locked.', []);
            }

            $app->update([
                'status' => 'employer_confirmed',
                'final_agreed_price' => $price,
                'slot_locked_at' => now(),
                'employer_confirmed_at' => now()
            ]);

            $job->increment('accepted_count');
        });

        // Dispatch reminder job
        SendHireReminderJob::dispatch($app)->delay(now()->addHours(24));
    }

    public function acceptOffer(Application $app): void
    {
        // Guard: app->status === 'employer_confirmed'
        if ($app->status !== 'employer_confirmed') {
            abort(422, 'Invalid application status for offer acceptance.', []);
        }

        $app->update([
            'status' => 'accepted',
            'contact_revealed' => true,
            'responded_at' => now()
        ]);

        // Check if job should be marked as closed_in_progress
        $job = JobPost::query()->findOrFail($app->job_post_id);
        Log::info("Checking job status for job {$job->id}. Accepted: {$job->accepted_count}, Slots: {$job->slots}", []);

        if ($job->accepted_count >= $job->slots) {
            $job->status = 'closed_in_progress';
            $job->save();
            Log::info("Job {$job->id} status updated to closed_in_progress", []);
        }
    }

    public function rejectOffer(Application $app): void
    {
        // Guard: app->status === 'employer_confirmed'
        if ($app->status !== 'employer_confirmed') {
            abort(422, 'Invalid application status for offer rejection.', []);
        }

        DB::transaction(function () use ($app) {
            $app->update([
                'status' => 'rejected',
                'responded_at' => now()
            ]);

            $app->job->decrement('accepted_count', 1, []);
        });

        // SMS to employer: Worker Rejected template
        $message = "SIKAP: Worker {$app->worker->name} has rejected your hire offer for [{$app->job->title}].";
        $this->semaphoreService->send((string)$app->job->employer->phone, (string)$message, null);
    }

    public function withdraw(Application $app): void
    {
        // Guard: app->status === 'pending'
        if ($app->status !== 'pending') {
            abort(422, 'Can only withdraw a pending application.', []);
        }
        $app->update(['status' => 'withdrawn']);
    }

    public function cancelHire(Application $app): void
    {
        // Guard: app->status in [employer_confirmed, pending_negotiation]
        if (!in_array($app->status, ['employer_confirmed', 'pending_negotiation'])) {
            abort(422, 'Cannot cancel hire at this stage.', []);
        }

        DB::transaction(function () use ($app) {
            if ($app->status === 'employer_confirmed') {
                $app->job->decrement('accepted_count', 1, []);
            }

            $app->update([
                'status' => 'rejected',
                'responded_at' => now()
            ]);
        });

        // SMS to worker: Employer Cancelled template
        $message = "SIKAP: Your hire for [{$app->job->title}] has been cancelled by the employer.";
        $this->semaphoreService->send((string)$app->worker->phone, (string)$message, null);
    }

    public function markJobComplete(JobPost $job): void
    {
        // Guard: job->status === 'closed_in_progress'
        if ($job->status !== 'closed_in_progress') {
            abort(422, 'Job must be in progress to be marked complete.', []);
        }

        $job->update([
            'status' => 'completed',
            'rating_window_expires_at' => now()->addDays(7)
        ]);

        // Update all accepted applications for this job
        $job->applications()->where('status', 'accepted')->update(['status' => 'completed']);
    }

    public function flagOffline(Application $app): void
    {
        // Guard: app->status === 'accepted'
        if ($app->status !== 'accepted') {
            abort(422, 'Application must be accepted to flag as completed offline.', []);
        }

        $app->update(['status' => 'completed']);

        // Check if all accepted apps for this job are completed
        $job = $app->job;
        $remainingAccepted = $job->applications()
            ->where('status', 'accepted')
            ->count();

        if ($remainingAccepted === 0) {
            $this->markJobComplete($job);
        }
    }
}
