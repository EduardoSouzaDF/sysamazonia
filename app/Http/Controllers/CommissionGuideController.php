<?php

namespace App\Http\Controllers;

use App\Services\CommissionGuides;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommissionGuideController extends Controller
{
    public function update(Request $request, CommissionGuides $guides): JsonResponse
    {
        $available = $guides->available($request->user());
        abort_if($available === [], 403);
        $validated = $request->validate([
            'guide' => ['required', Rule::in($available)],
            'version' => ['required', 'integer', Rule::in([CommissionGuides::VERSION])],
            'step' => ['required', 'integer', 'between:0,30'],
            'status' => ['required', Rule::in(['active', 'paused', 'completed'])],
        ]);
        DB::table('user_guide_progress')->upsert([
            [...$validated, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()],
        ], ['user_id', 'guide', 'version'], ['step', 'status', 'updated_at']);

        return response()->json(['saved' => true]);
    }
}
