<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index()
    {
        $skills = Skill::select('id', 'name', 'category')->orderBy('name')->get();
        return response()->json($skills);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string'
        ]);

        $name = trim($request->name);

        $skill = Skill::firstOrCreate(
            ['name' => $name],
            ['category' => $request->category ?? 'Other']
        );

        return response()->json($skill, 201);
    }
}
