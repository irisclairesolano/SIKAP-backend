<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\User;
use App\Models\WorkerCharacterReference;
use App\Models\WorkerExperience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'workerProfile.skills',
            'workerProfile.experiences',
            'workerProfile.references',
            'employerProfile'
        ]);

        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'worker') {
            $validator = Validator::make($request->all(), [
                'bio' => 'nullable|string',
                'availability_status' => 'nullable|in:available,unavailable'
            ], [], []);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $profile = $user->workerProfile ?? $user->workerProfile()->create();
            $profile->update($request->only(['bio', 'availability_status']));

        } elseif ($user->role === 'employer') {
            $validator = Validator::make($request->all(), [
                'description' => 'nullable|string',
                'contact_info' => 'nullable|string'
            ], [], []);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $profile = $user->employerProfile ?? $user->employerProfile()->create();
            $profile->update($request->only(['description', 'contact_info']));
        }

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function addExperience(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can add experiences.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'job_title' => 'required|string',
            'employer_name' => 'nullable|string',
            'duration' => 'nullable|string',
            'description' => 'nullable|string'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $profile = $user->workerProfile ?? $user->workerProfile()->create();

        $experience = $profile->experiences()->create($request->all());

        return response()->json(['message' => 'Experience added successfully.', 'experience' => $experience], 201);
    }

    public function removeExperience(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can remove experiences.'], 403);
        }

        $experience = WorkerExperience::query()->find($id);

        if (!$experience || $experience->workerProfile->user_id !== $user->id) {
            return response()->json(['message' => 'Experience not found.'], 404);
        }

        $experience->delete();

        return response()->json(['message' => 'Experience removed successfully.']);
    }

    public function addReference(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can add references.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'relationship' => 'required|string|max:255'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $profile = $user->workerProfile ?? $user->workerProfile()->create();

        $reference = $profile->references()->create($request->all());

        return response()->json(['message' => 'Reference added successfully.', 'reference' => $reference], 201);
    }

    public function removeReference(Request $request, int $id)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can remove references.'], 403);
        }

        $reference = WorkerCharacterReference::query()->find($id);

        if (!$reference || $reference->workerProfile->user_id !== $user->id) {
            return response()->json(['message' => 'Reference not found.'], 404);
        }

        $reference->delete();

        return response()->json(['message' => 'Reference removed successfully.']);
    }

    public function syncSkills(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'worker') {
            return response()->json(['message' => 'Only workers can sync skills.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'skill_ids' => 'required|array',
            'skill_ids.*' => 'exists:skills,id'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $profile = $user->workerProfile ?? $user->workerProfile()->create();
        $profile->skills()->sync($request->skill_ids);

        return response()->json(['message' => 'Skills updated successfully.']);
    }
}
