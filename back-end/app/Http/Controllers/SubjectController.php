<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use Illuminate\Validation\Rule;


class SubjectController extends Controller
{

    public function index(Request $request)
    {
        // Eager load the 'assessments' relationship for each subject.
        // This assumes you have an 'assessments' method defined in your Subject model.
        $all_subject = $request->user()
            ->subjects()
            ->with([
                'assessments' => function ($query) {
                    // Optional: You can filter/order the assessments here if needed, 
                    // e.g., only include assessments for the current quarter.
                    $query->orderBy('date_taken', 'desc');
                }
            ])
            ->get();

        if ($all_subject->isNotEmpty()) {
            // The resulting JSON will now have an 'assessments' array nested inside each subject object.
            return response()->json([
                'All subjects' => $all_subject,
            ]);
        } else {
            return response()->json([
                'message' => 'No subjects found',
            ]);
        }
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_name' => 'required|string|max:50',
            'color' => 'nullable|string|max:50',
        ]);

        $subject = $request->user()->subjects()->create($validated);

        return response()->json([
            'message' => 'Subject created successfully',
            'subject' => $subject
        ], 201);
    }


    public function show(Request $request, $subject_name)
    {

        $subject = $request->user()->subjects()
        ->Where('subject_name', 'like', '%'. $subject_name . '%')
        ->get();

        if (!$subject) {
            return response()->json(['message' => 'Subject not found'], 404);
        }

        return response()->json($subject);
    }

    // public function update(Request $request, $id)
    // {
    //     $subject = $request->user()->subjects()->find($id);

    //     if (!$subject) {
    //         return response()->json(['message' => 'Subject not found'], 404);
    //     }

    //     $validated = $request->validate([
    //         'name' => 'sometimes|string|max:255',
    //         'color' => 'nullable|string',
    //         'target_grade' => 'nullable|numeric',
    //     ]);

    //     $subject->update($validated);

    //     return response()->json([
    //         'message' => 'Subject updated successfully',
    //         'subject' => $subject
    //     ]);
    // }

    // // Delete a subject
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
