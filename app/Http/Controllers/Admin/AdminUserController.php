<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $query = User::with(['workerProfile', 'employerProfile']);

        // Filters
        if ($request->role) {
            $query->where('role', $request->role);
        }
        if ($request->has('is_suspended')) {
            $query->where('is_suspended', $request->boolean('is_suspended'));
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ILIKE', '%' . $request->search . '%')
                  ->orWhere('email', 'ILIKE', '%' . $request->search . '%');
            });
        }
        if ($request->municipality) {
            $query->where('municipality', $request->municipality);
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_suspended' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $targetUser = User::findOrFail($id);
        
        if ($targetUser->role === 'admin') {
            return response()->json(['message' => 'Cannot modify admin accounts.'], 403);
        }

        $targetUser->update(['is_suspended' => $request->is_suspended]);

        return response()->json(['message' => 'User updated successfully.']);
    }
}
