<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /** ------------------------------------------
     *  BASIC CRUD & FETCHING
     *  ------------------------------------------ */

    // List all assessments for logged-in user
    public function index(Request $request)
    {
        return $request->user()
            ->assessments()
            ->with('subject')
            ->get();
    }

    // Store assessment
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id'      => 'required|exists:subjects,id',
            'name_assessment' => 'required|string|max:255',
            'type_quarter'    => 'required|string|in:preliminary,midterm,pre_final,final',
            'type_activity'   => 'required|string|in:quiz,exam,assignment,project',
            'mode'            => 'required|string|in:f2f,online',
            'score'           => 'required|numeric|min:0',
            'total_items'     => 'required|numeric|min:0',
            'date_taken'      => 'nullable|date',
        ]);

        $assessment = $request->user()->assessments()->create($validated);

        return response()->json([
            'message'    => 'Assessment created successfully',
            'assessment' => $assessment
        ], 201);
    }

    // Show assessment by quarter
    public function show(Request $request, $quarter)
    {
        $assessments = $request->user()
            ->assessments()
            ->with('subject')
            ->where('type_quarter', $quarter)
            ->get();

        if ($assessments->isEmpty()) {
            return response()->json(['message' => 'No assessments found'], 404);
        }

        return response()->json($assessments);
    }

    // Update an assessment
    public function update(Request $request, $id)
    {
        $assessment = $request->user()->assessments()->find($id);

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'type'        => 'sometimes|string|in:quiz,test,exam,assignment,project',
            'grade'       => 'sometimes|numeric|min:0',
            'max_grade'   => 'sometimes|numeric|min:1',
            'weight'      => 'nullable|numeric|min:0',
            'date_taken'  => 'nullable|date',
        ]);

        $assessment->update($validated);

        return response()->json([
            'message'    => 'Assessment updated successfully',
            'assessment' => $assessment
        ]);
    }

    // Delete assessment
    public function destroy(Request $request, $id)
    {
        $assessment = $request->user()->assessments()->find($id);

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        $assessment->delete();

        return response()->json(['message' => 'Assessment deleted successfully']);
    }



    /** ------------------------------------------
     *  INDIVIDUAL GRADE CALCULATIONS
     *  These repeat same pattern → consolidated
     *  ------------------------------------------ */

    // private function calculate(Request $request, $quarter, $subject_id, $type)
    // {
    //     $method = "calculation{$type}";
    //     return Assessment::$method($request, $quarter, $subject_id);
    // }

public function getQuizPercent(Request $r, $q, $id) {
    $data = Assessment::getAllTotalPercentages($r, $q, $id)->getData();
    return response()->json($data->activities->quiz);
}

public function getExamPercent(Request $r, $q, $id) {
    $data = Assessment::getAllTotalPercentages($r, $q, $id)->getData();
    return response()->json($data->activities->exam);
}

public function getAssignmentPercent(Request $r, $q, $id) {
    $data = Assessment::getAllTotalPercentages($r, $q, $id)->getData();
    return response()->json($data->activities->assignment);
}

public function getProjectPercent(Request $r, $q, $id) {
    $data = Assessment::getAllTotalPercentages($r, $q, $id)->getData();
    return response()->json($data->activities->project);
}


    /** ------------------------------------------
     *  QUARTERLY & SUBJECT-WIDE CALCULATIONS
     *  ------------------------------------------ */

    public function getAllTotalPercentages(Request $r, $q, $id)
    {
        return Assessment::getAllTotalPercentages($r, $q, $id);
    }

    public function getOverallGrades(Request $r, $quarter)
    {
        return Assessment::getAllTotalPercentagesForAllSubjects($r, $quarter);
    }

    public function getSpecificGrade(int $subjectId)
    {
        return Assessment::getSubjectGradesAllQuarters($subjectId);
    }


    /** ------------------------------------------
     *  COMPLETE GRADE OVERVIEW — ALL SUBJECTS
     *  ------------------------------------------ */
    public function getOverallGradesAllQuarters(){
        return Assessment::getOverallGradesAllQuarters();
    }


    public function getallActivitySpecificQuarter(Request $request, string $quarter, int $subjectId)
    {
        // Use the authenticated user's relationship to fetch only their assessments
        $assessments = $request->user()
            ->assessments()
            ->with('subject') // Eager load subject relationship for context
            ->where('type_quarter', $quarter)
            ->where('subject_id', $subjectId)
            ->get();

        if ($assessments->isEmpty()) {
            return response()->json([
                'message' => 'No activities found for the specified quarter and subject.',
                'quarter' => $quarter,
                'subject_id' => $subjectId,
                'data' => []
            ], 404);
        }

        return response()->json($assessments);
    }
}
