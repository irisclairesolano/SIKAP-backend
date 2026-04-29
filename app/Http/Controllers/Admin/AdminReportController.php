<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $query = Report::with(['reporter']);

        // Filters
        $status = $request->get('status', 'open');
        $query->where('status', $status);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $reports = $query->paginate(15);

        return response()->json($reports);
    }

    public function resolve(Request $request, $id)
    {
        $user = $request->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $validator = validator()->make($request->all(), [
            'status' => 'required|in:resolved,dismissed'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $report = Report::findOrFail($id);
        $report->update([
            'status' => $request->status,
            'resolved_at' => now()
        ]);

        return response()->json(['message' => 'Report updated.']);
    }
}
