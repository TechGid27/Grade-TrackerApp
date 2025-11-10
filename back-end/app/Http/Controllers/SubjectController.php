<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Validation\Rule;


class SubjectController extends Controller
{

   public function index(Request $request)
    {
        $subjects = $request->user()
            ->subjects()
            ->withCount('assessments')
            ->with(['assessments' => function ($query) {
                $query->select('id','name','type', 'subject_id', 'grade', 'max_grade', 'weight','date_taken');
            }])
            ->get()
            ->map(function ($subject) {
                $totalWeighted = 0;
                $totalWeight = 0;

                foreach ($subject->assessments as $assessment) {
                    $percentage = ($assessment->grade / $assessment->max_grade) * 100;
                    $totalWeighted += $percentage * ($assessment->weight ?? 1);
                    $totalWeight += ($assessment->weight ?? 1);
                }

                $subject->current_grade = $totalWeight > 0
                    ? round($totalWeighted / $totalWeight, 2)
                    : 0;

                return $subject;
            });

        return response()->json([
            'subjects' => $subjects,
            'total_subjects' => $subjects->count(),
        ]);
    }

   public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                }),
            ],
            'color' => 'nullable|string',
            'target_grade' => 'nullable|numeric',
        ]);

        $subject = $request->user()->subjects()->create($validated);

        return response()->json([
            'message' => 'Subject created successfully',
            'subject' => $subject
        ], 201);
    }
    public function show(Request $request, $name)
    {

        $subject = $request->user()->subjects()
        ->Where('name', 'like', '%'. $name . '%')
        ->get();

        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = $request->user()->subjects()->find($id);

        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'nullable|string',
            'target_grade' => 'nullable|numeric',
        ]);

        $subject->update($validated);

        return response()->json([
            'message' => 'Subject updated successfully',
            'subject' => $subject
        ]);
    }

    // Delete a subject
    public function destroy(Request $request, $id)
    {
        $subject = $request->user()->subjects()->find($id);

        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        $subject->delete();

        return response()->json(['message' => 'Subject deleted successfully']);
    }
}
