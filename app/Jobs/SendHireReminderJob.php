<?php

namespace App\Jobs;

use App\Models\Application;
use App\Services\SemaphoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendHireReminderJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Application $application
    ) {}

    public function handle(SemaphoreService $semaphoreService): void
    {
        // SMS to worker: Stage 3 template
        $message = "SIKAP: Action required. Confirm your hire for [{$this->application->job->title}]. The employer may cancel after 48 hours.";
        $semaphoreService->send($this->application->worker->phone, $message);
    }
}
