<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\JobPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'reportable_type' => 'required|in:user,job_post,application',
            'reportable_id' => 'required|integer',
            'type' => 'required|in:harassment,fake_account,inappropriate_job,other',
            'description' => 'nullable|string|max:1000'
        ], [], []);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Verify the reportable entity exists
        $reportableType = $request->reportable_type;
        $reportableId = $request->reportable_id;

        switch ($reportableType) {
            case 'user':
                $entity = User::query()->find($reportableId);
                break;
            case 'job_post':
                $entity = JobPost::query()->find($reportableId);
                break;
            case 'application':
                $entity = Application::query()->find($reportableId);
                break;
            default:
                return response()->json(['message' => 'Invalid reportable type.'], 422);
        }

        if (!$entity) {
            return response()->json(['message' => 'Reportable entity not found.'], 404);
        }

        $report = Report::query()->create([
            'reporter_id' => $user->id,
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
            'type' => $request->type,
            'description' => $request->description,
            'status' => 'open'
        ]);

        return response()->json(['message' => 'Report submitted.', 'report_id' => $report->id], 201);
    }
}
