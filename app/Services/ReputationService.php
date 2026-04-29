<?php

namespace App\Services;

use App\Models\User;
use App\Models\Review;

class ReputationService
{
    public function recalculate(User $user): void
    {
        $avg = Review::where('reviewee_id', $user->id)->avg('overall_rating') ?? 0.00;
        $score = round($avg, 2);

        $user->update(['reputation_score' => $score]);

        if ($user->role === 'worker' && $user->workerProfile) {
            $user->workerProfile->update(['reputation_score' => $score]);
        } elseif ($user->role === 'employer' && $user->employerProfile) {
            $user->employerProfile->update(['reputation_score' => $score]);
        }
    }
}
