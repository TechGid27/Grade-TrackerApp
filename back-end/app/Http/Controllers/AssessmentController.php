<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    // List all assessments for logged-in user
    public function index(Request $request)
    {
        return $request->user()->assessments()->with('subject')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:quiz,test,exam,assignment,project',
            'grade' => 'required|numeric|min:0',
            'max_grade' => 'required|numeric|min:1',
            'weight' => 'nullable|numeric|min:0',
            'date_taken' => 'nullable|date',
        ]);

        $assessment = $request->user()->assessments()->create($validated);

        return response()->json([
            'message' => 'Assessment created successfully',
            'assessment' => $assessment
        ], 201);
    }

    // Show a single assessment
    public function show(Request $request, $id)
    {
        $assessment = $request->user()
        ->assessments()
        ->with('subject')
        ->find($id);

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        return response()->json($assessment);
    }

    // Update an assessment
    public function update(Request $request, $id)
    {
        $assessment = $request->user()->assessments()->find($id);

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:quiz,test,exam,assignment,project',
            'grade' => 'sometimes|numeric|min:0',
            'max_grade' => 'sometimes|numeric|min:1',
            'weight' => 'nullable|numeric|min:0',
            'date_taken' => 'nullable|date',
        ]);

        $assessment->update($validated);

        return response()->json([
            'message' => 'Assessment updated successfully',
            'assessment' => $assessment
        ]);
    }

    // Delete an assessment
    public function destroy(Request $request, $id)
    {
        $assessment = $request->user()->assessments()->find($id);

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        $assessment->delete();

        return response()->json(['message' => 'Assessment deleted successfully']);
    }
}
