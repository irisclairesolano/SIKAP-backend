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
}
