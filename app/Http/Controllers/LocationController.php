<?php

namespace App\Http\Controllers;

use App\Models\Municipality;
use App\Models\Barangay;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/v1/locations/municipalities
     * Returns all municipalities in Sorsogon — no auth required
     */
    public function municipalities()
    {
        $municipalities = Municipality::orderBy('name')->pluck('name');

        return response()->json([
            'data' => $municipalities,
            'total' => $municipalities->count(),
        ]);
    }

    /**
     * GET /api/v1/locations/barangays?municipality=Sorsogon City
     * Returns barangays for a given municipality — no auth required
     */
    public function barangays(Request $request)
    {
        $request->validate([
            'municipality' => 'required|string|exists:municipalities,name',
        ]);

        $barangays = Barangay::whereHas('municipality', function ($q) use ($request) {
            $q->where('name', $request->municipality);
        })
        ->orderBy('name')
        ->pluck('name');

        return response()->json([
            'data' => $barangays,
            'municipality' => $request->municipality,
            'total' => $barangays->count(),
        ]);
    }
}
