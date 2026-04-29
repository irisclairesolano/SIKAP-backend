<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'job_post_id' => $this->job_post_id,
            'status' => $this->status,
            'cover_note' => $this->cover_note,
            'applied_at' => $this->applied_at,
        ];

        // final_agreed_price: only if status in [employer_confirmed, accepted]
        if (in_array($this->status, ['employer_confirmed', 'accepted'])) {
            $data['final_agreed_price'] = $this->final_agreed_price;
        }

        $worker = $this->whenLoaded('worker');
        if ($worker) {
            $workerData = [
                'id' => $worker->id,
                'name' => $worker->name,
                'barangay' => $worker->barangay,
                'reputation_score' => $worker->reputation_score,
                'verification_badge' => $worker->verification_badge,
                'email' => null, // NEVER disclosed
            ];

            // Load worker profile data
            if ($worker->workerProfile) {
                $workerData['skills'] = $worker->workerProfile->skills->pluck('name');
                $workerData['experiences'] = $worker->workerProfile->experiences;
            }

            // character_references: only if references_revealed = true
            if ($this->references_revealed && $worker->workerProfile) {
                $workerData['character_references'] = $worker->workerProfile->references;
            } else {
                $workerData['character_references'] = null;
            }

            // phone: only if contact_revealed = true
            if ($this->contact_revealed) {
                $workerData['phone'] = $worker->phone;
            } else {
                $workerData['phone'] = null;
            }

            $data['worker'] = $workerData;
        }

        return $data;
    }
}
