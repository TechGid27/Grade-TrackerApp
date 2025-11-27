<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'name_assessment',
        'type_quarter',
        'type_activity',
        'mode', // The key is 'mode' as per the $fillable array
        'score',
        'total_items',
        'date_taken',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /* -----------------------------------------------------------------
     * Configurable constants
     * ----------------------------------------------------------------- */
    private const ACTIVITIES = ['quiz', 'exam', 'assignment', 'project'];

    /**
     * Getter for the ACTIVITIES constant (added for Controller access).
     * @return array
     */
    public static function getActivitiesList(): array
    {
        return self::ACTIVITIES;
    }

    /**
     * Transmute a raw percentage (0-100) to grading scale 1.00 - 5.00
     * Based on ACLC / AMA Grading System
     */
    private static function transmuteGrade(?float $percentage): ?float
    {
        if ($percentage === null) {
            return null;
        }

        // Optimization: Use a map for faster lookup if the scale were larger, but if/else is fine for this size
        if ($percentage >= 98) return 1.00;
        if ($percentage >= 95) return 1.25;
        if ($percentage >= 92) return 1.50;
        if ($percentage >= 89) return 1.75;
        if ($percentage >= 86) return 2.00;
        if ($percentage >= 83) return 2.25;
        if ($percentage >= 80) return 2.50;
        if ($percentage >= 77) return 2.75;
        if ($percentage >= 75) return 3.00;

        return 5.00;
    }

    /**
     * Compute activity totals (f2f, online) from a collection of assessments
     * Returns raw percentages (0-100)
     *
     * @param Collection $assessmentsFiltered
     * @param string $activity
     * @return object
     */
    public static function computeActivityFromCollection(Collection $assessmentsFiltered, string $activity): object
    {
        $activityAssessments = $assessmentsFiltered->where('type_activity', $activity);

        // --- Face to Face Calculations ---
        // NOTE: Using 'mode' field as defined in $fillable
        $f2fAssessments = $activityAssessments->where('mode', 'f2f');
        $f2fScore = $f2fAssessments->sum('score');
        $f2fItems = $f2fAssessments->sum('total_items');

        // Formula: (Score / Items) * 100
        $f2fPercent = $f2fItems > 0 ? ($f2fScore / $f2fItems) * 100 : 0;

        // --- Online Calculations ---
        $onlineAssessments = $activityAssessments->where('mode', 'online');
        $onlineScore = $onlineAssessments->sum('score');
        $onlineItems = $onlineAssessments->sum('total_items');

        // Formula: (Score / Items) * 100
        $onlinePercent = $onlineItems > 0 ? ($onlineScore / $onlineItems) * 100 : 0;

        return (object)[
            'f2f_percent'   => $f2fPercent,
            'online_percent'=> $onlinePercent,
            'f2f_items'     => $f2fItems,
            'online_items'  => $onlineItems,
            'f2f_score'     => $f2fScore,
            'online_score'  => $onlineScore,
        ];
    }

    /**
     * Get total percentages for a single subject & quarter, with breakdown.
     * @param Request $request
     * @param string $type_quarter
     * @param int $subject_id
     */
    public static function getAllTotalPercentages($request, $type_quarter, $subject_id)
    {
        // Load all assessments for this user / subject / quarter in a single query
        $assessments = $request->user()
            ->assessments()
            ->where('subject_id', $subject_id)
            ->where('type_quarter', $type_quarter)
            ->get();

        $activitiesResult = [];
        $sumF2FPercent = 0.0;
        $sumOnlinePercent = 0.0;
        $hasData = false;

        foreach (self::ACTIVITIES as $activity) {
            $totals = self::computeActivityFromCollection($assessments, $activity);

            $activitiesResult[$activity] = [
                'partial_grades' => [
                    'face_to_face' => [
                        'score_obtained' => $totals->f2f_score,
                        'total_possible' => $totals->f2f_items,
                        'percentage'     => round($totals->f2f_percent, 2),
                    ],
                    'online' => [
                        'score_obtained' => $totals->online_score,
                        'total_possible' => $totals->online_items,
                        'percentage'     => round($totals->online_percent, 2),
                    ],
                ]
            ];

            // Accumulate percentages to average later
            $sumF2FPercent += $totals->f2f_percent;
            $sumOnlinePercent += $totals->online_percent;

            if ($totals->f2f_items > 0 || $totals->online_items > 0) {
                $hasData = true;
            }
        }

        $rawScoreF2F = null;
        $rawScoreOnline = null;
        $rawScoreOverall = null;

        $finalGradeF2F = null;
        $finalGradeOnline = null;
        $finalGradeOverall = null;


        if ($hasData) {
            // 1. Calculate Average Percentage across the 4 activities
            $count = count(self::ACTIVITIES);
            $avgF2F = $sumF2FPercent / $count;
            $avgOnline = $sumOnlinePercent / $count;

            // 2. Calculate Breakdowns
            // F2F-Only Grade (100% F2F average)
            $rawScoreF2F = $avgF2F;
            $finalGradeF2F = self::transmuteGrade($rawScoreF2F);

            // Online-Only Grade (100% Online average)
            $rawScoreOnline = $avgOnline;
            $finalGradeOnline = self::transmuteGrade($rawScoreOnline);

            // Overall Grade (60% F2F + 40% Online Formula)
            $rawScoreOverall = ($avgF2F * 0.60) + ($avgOnline * 0.40);
            $finalGradeOverall = self::transmuteGrade($rawScoreOverall);
        }

        return response()->json([
            'subject_id'         => $subject_id,
            'type_quarter'       => $type_quarter,
            'activities'         => $activitiesResult,

            // F2F Breakdown
            'f2f_breakdown' => [
                'avg_percent' => round($rawScoreF2F ?? 0, 4),
                'raw_score' => $rawScoreF2F !== null ? round($rawScoreF2F, 4) : "No Data",
                'final_grade' => $finalGradeF2F === null ? "No Grade" : $finalGradeF2F,
            ],

            // Online Breakdown
            'online_breakdown' => [
                'avg_percent' => round($rawScoreOnline ?? 0, 4),
                'raw_score' => $rawScoreOnline !== null ? round($rawScoreOnline, 4) : "No Data",
                'final_grade' => $finalGradeOnline === null ? "No Grade" : $finalGradeOnline,
            ],

            // Overall Breakdown (60/40)
            'overall_breakdown' => [
                'avg_f2f_percent'    => round($avgF2F ?? 0, 4), // Kept for reference
                'avg_online_percent' => round($avgOnline ?? 0, 4), // Kept for reference
                'raw_score'          => $rawScoreOverall !== null ? round($rawScoreOverall, 4) : "No Data",
                'final_grade'        => $finalGradeOverall === null ? "No Grade" : $finalGradeOverall,
            ],
        ]);
    }

    /**
     * Get totals across all subjects for a single quarter, with breakdown.
     * @param Request $request
     * @param string $type_quarter
     */
    public static function getAllTotalPercentagesForAllSubjects($request, $type_quarter)
    {
        $assessments = $request->user()
            ->assessments()
            ->where('type_quarter', $type_quarter)
            ->get();

        $bySubject = $assessments->groupBy('subject_id');
        $subjects = $request->user()->subjects()->get();

        $results = [];
        $sumOfFinalGrades = 0.0; // Overall final grades sum for GWA
        $gradedSubjectsCount = 0;

        foreach ($subjects as $subject) {
            $subjectAssessments = $bySubject->get($subject->id, collect());

            $sumF2FPercent = 0.0;
            $sumOnlinePercent = 0.0;
            $hasData = false;

            // Calculate totals for this subject
            foreach (self::ACTIVITIES as $activity) {
                $totals = self::computeActivityFromCollection($subjectAssessments, $activity);

                $sumF2FPercent += $totals->f2f_percent;
                $sumOnlinePercent += $totals->online_percent;

                if ($totals->f2f_items > 0 || $totals->online_items > 0) {
                    $hasData = true;
                }
            }

            $rawScoreF2F = null;
            $rawScoreOnline = null;
            $rawScoreOverall = null;

            $finalGradeF2F = null;
            $finalGradeOnline = null;
            $finalGradeOverall = null;
            $status = 'No Grade';

            if ($hasData) {
                $count = count(self::ACTIVITIES);
                $avgF2F = $sumF2FPercent / $count;
                $avgOnline = $sumOnlinePercent / $count;

                // 1. F2F-Only Grade (100% F2F average)
                $rawScoreF2F = $avgF2F;
                $finalGradeF2F = self::transmuteGrade($rawScoreF2F);

                // 2. Online-Only Grade (100% Online average)
                $rawScoreOnline = $avgOnline;
                $finalGradeOnline = self::transmuteGrade($rawScoreOnline);

                // 3. Overall Grade (60% F2F + 40% Online Formula)
                $rawScoreOverall = ($avgF2F * 0.60) + ($avgOnline * 0.40);
                $finalGradeOverall = self::transmuteGrade($rawScoreOverall);

                $status = $finalGradeOverall !== null && $finalGradeOverall <= 3.0 ? 'Passing' : 'Failed';

                if ($finalGradeOverall !== null) {
                    $sumOfFinalGrades += $finalGradeOverall;
                    $gradedSubjectsCount++;
                }
            }

            $results[$subject->subject_name] = [
                'subject_id' => $subject->id,
                'status' => $status,

                // F2F Breakdown
                'f2f_breakdown' => [
                    'raw_score_percent' => $rawScoreF2F !== null ? round($rawScoreF2F, 4) : null,
                    'final_grade' => $finalGradeF2F,
                ],

                // Online Breakdown
                'online_breakdown' => [
                    'raw_score_percent' => $rawScoreOnline !== null ? round($rawScoreOnline, 4) : null,
                    'final_grade' => $finalGradeOnline,
                ],

                // Overall Breakdown (60/40)
                'overall_breakdown' => [
                    'raw_score_percent' => $rawScoreOverall !== null ? round($rawScoreOverall, 4) : null,
                    'final_grade' => $finalGradeOverall,
                ],
            ];
        }

        $overallAverage = $gradedSubjectsCount > 0 ? ($sumOfFinalGrades / $gradedSubjectsCount) : 0;

        return response()->json([
            'message' => "Overall grades for {$type_quarter}, separated by mode breakdown",
            'quarter' => $type_quarter,
            'subjects' => $results,
            'overall_average' => $gradedSubjectsCount > 0 ? round($overallAverage, 4) : null,
        ]);
    }

    /**
     * Get overall grades across all quarters for each subject. (PREVIOUSLY UPDATED)
     */
    public static function getOverallGradesAllQuarters()
    {
        $request = request();
        $quarters = ['preliminary', 'midterm', 'pre_final', 'final'];

        // 1. Fetch all relevant assessments for the user
        $allAssessments = $request->user()
            ->assessments()
            ->whereIn('type_quarter', $quarters)
            ->get();

        $subjects = $request->user()->subjects()->get();
        if ($subjects->isEmpty()) {
            return response()->json([
                'message' => 'No subjects found',
                'subjects' => [],
                'overall_average' => null,
            ]);
        }

        $results = [];
        $overallSubjectGradesSum = 0.0;
        $gradedSubjectsCount = 0;

        foreach ($subjects as $subject) {
            $subjectAssessments = $allAssessments->where('subject_id', $subject->id);

            $quarterDataMap = [];
            $sumOfQuarterGrades = 0.0; // Sum of final_grade_overall for subject average
            $gradedQuartersCount = 0;

            foreach ($quarters as $quarter) {
                // Filter assessments for the current quarter
                $quarterAssessments = $subjectAssessments->filter(fn ($item) => $item->type_quarter === $quarter);

                $sumF2FPercent = 0.0;
                $sumOnlinePercent = 0.0;
                $hasData = false;

                // Compute total percentages for F2F and Online across all activities
                foreach (self::ACTIVITIES as $activity) {
                    $totals = self::computeActivityFromCollection($quarterAssessments, $activity);
                    $sumF2FPercent += $totals->f2f_percent;
                    $sumOnlinePercent += $totals->online_percent;

                    if ($totals->f2f_items > 0 || $totals->online_items > 0) {
                        $hasData = true;
                    }
                }

                $rawScoreF2F = null;
                $rawScoreOnline = null;
                $rawScoreOverall = null;

                $finalGradeF2F = null;
                $finalGradeOnline = null;
                $finalGradeOverall = null;

                if ($hasData) {
                    $count = count(self::ACTIVITIES);
                    // Average percentage for F2F and Online activities
                    $avgF2F = $sumF2FPercent / $count;
                    $avgOnline = $sumOnlinePercent / $count;

                    // 1. F2F-Only Grade (100% F2F average)
                    $rawScoreF2F = $avgF2F;
                    $finalGradeF2F = self::transmuteGrade($rawScoreF2F);

                    // 2. Online-Only Grade (100% Online average)
                    $rawScoreOnline = $avgOnline;
                    $finalGradeOnline = self::transmuteGrade($rawScoreOnline);

                    // 3. Overall Grade (60% F2F + 40% Online Formula)
                    $rawScoreOverall = ($avgF2F * 0.60) + ($avgOnline * 0.40);
                    $finalGradeOverall = self::transmuteGrade($rawScoreOverall);

                    // Only the Overall Grade contributes to the Subject Final Average
                    if ($finalGradeOverall !== null) {
                        $sumOfQuarterGrades += $finalGradeOverall;
                        $gradedQuartersCount++;
                    }
                }

                $quarterDataMap[$quarter] = [
                    'f2f' => [
                        'raw_score' => $rawScoreF2F !== null ? round($rawScoreF2F, 4) : null,
                        'final_grade' => $finalGradeF2F,
                    ],
                    'online' => [
                        'raw_score' => $rawScoreOnline !== null ? round($rawScoreOnline, 4) : null,
                        'final_grade' => $finalGradeOnline,
                    ],
                    'overall' => [
                        'raw_score' => $rawScoreOverall !== null ? round($rawScoreOverall, 4) : null,
                        'final_grade' => $finalGradeOverall,
                    ],
                ];
            }

            if ($gradedQuartersCount > 0) {
                $subjectFinalAverage = $sumOfQuarterGrades / $gradedQuartersCount;
                $status = $subjectFinalAverage <= 3.0 ? 'Passing' : 'Failed';
                $overallSubjectGradesSum += $subjectFinalAverage;
                $gradedSubjectsCount++;
            } else {
                $subjectFinalAverage = null;
                $status = 'No Grades Yet';
            }

            $results[$subject->subject_name] = [
                'quarters' => $quarterDataMap,
                'subject_final_average' => $subjectFinalAverage !== null ? round($subjectFinalAverage, 4) : null,
                'status' => $status,
            ];
        }

        $overallAverage = $gradedSubjectsCount > 0 ? round($overallSubjectGradesSum / $gradedSubjectsCount, 4) : null;

        return response()->json([
            'message' => 'Overall grades for all subjects across all quarters, separated by mode',
            'quarters' => $quarters,
            'subjects' => $results,
            'overall_average' => $overallAverage,
        ]);
    }

    /**
     * Get subject grades across all quarters, with breakdown.
     * @param int $subjectId
     */
    public static function getSubjectGradesAllQuarters(int $subjectId)
    {
        $request = request();
        $quarters = ['preliminary', 'midterm', 'pre_final', 'final'];

        // 1. Fetch the specific subject
        $subject = $request->user()
            ->subjects()
            ->find($subjectId);

        if (!$subject) {
            return response()->json([
                'message' => 'Subject not found for the current user.',
                'subject_id' => $subjectId,
                'quarters' => [],
                'subject_final_average' => null,
            ], 404);
        }

        // 2. Fetch all relevant assessments for this specific subject and quarters
        $allAssessments = $request->user()
            ->assessments()
            ->where('subject_id', $subjectId)
            ->whereIn('type_quarter', $quarters)
            ->get();

        $quarterDataMap = [];
        $sumOfQuarterGrades = 0.0; // Sum of final_grade_overall for subject average
        $gradedQuartersCount = 0;

        // 3. Loop through quarters to calculate grades
        foreach ($quarters as $quarter) {
            // IMPORTANT: Filter the collection by the quarter
            $quarterAssessments = $allAssessments->filter(fn ($item) => $item->type_quarter === $quarter);

            $sumF2FPercent = 0.0;
            $sumOnlinePercent = 0.0;
            $hasData = false;

            // Compute totals for each activity type
            foreach (self::ACTIVITIES as $activity) {
                $totals = self::computeActivityFromCollection($quarterAssessments, $activity);
                $sumF2FPercent += $totals->f2f_percent;
                $sumOnlinePercent += $totals->online_percent;

                if ($totals->f2f_items > 0 || $totals->online_items > 0) {
                    $hasData = true;
                }
            }

            $rawScoreF2F = null;
            $rawScoreOnline = null;
            $rawScoreOverall = null;

            $finalGradeF2F = null;
            $finalGradeOnline = null;
            $finalGradeOverall = null;

            if ($hasData) {
                $count = count(self::ACTIVITIES);
                $avgF2F = $sumF2FPercent / $count;
                $avgOnline = $sumOnlinePercent / $count;

                // 1. F2F-Only Grade (100% F2F average)
                $rawScoreF2F = $avgF2F;
                $finalGradeF2F = self::transmuteGrade($rawScoreF2F);

                // 2. Online-Only Grade (100% Online average)
                $rawScoreOnline = $avgOnline;
                $finalGradeOnline = self::transmuteGrade($rawScoreOnline);

                // 3. Overall Grade (60% F2F + 40% Online Formula)
                $rawScoreOverall = ($avgF2F * 0.60) + ($avgOnline * 0.40);
                $finalGradeOverall = self::transmuteGrade($rawScoreOverall);

                if ($finalGradeOverall !== null) {
                    $sumOfQuarterGrades += $finalGradeOverall;
                    $gradedQuartersCount++;
                }
            }

            $quarterDataMap[$quarter] = [
                'f2f' => [
                    'raw_score' => $rawScoreF2F !== null ? round($rawScoreF2F, 4) : null,
                    'final_grade' => $finalGradeF2F,
                ],
                'online' => [
                    'raw_score' => $rawScoreOnline !== null ? round($rawScoreOnline, 4) : null,
                    'final_grade' => $finalGradeOnline,
                ],
                'overall' => [
                    'raw_score' => $rawScoreOverall !== null ? round($rawScoreOverall, 4) : null,
                    'final_grade' => $finalGradeOverall,
                ],
            ];
        }

        // 4. Calculate final subject average
        if ($gradedQuartersCount > 0) {
            $subjectFinalAverage = $sumOfQuarterGrades / $gradedQuartersCount;
            $status = $subjectFinalAverage <= 3.0 ? 'Passing' : 'Failed';
        } else {
            $subjectFinalAverage = null;
            $status = 'No Grades Yet';
        }

        // 5. Return the final structured response
        return response()->json([
            'message' => "Grades for subject '{$subject->subject_name}' across all quarters, separated by mode breakdown.",
            'subject_name' => $subject->subject_name,
            'subject_id' => $subject->id,
            'quarters' => $quarterDataMap,
            'subject_final_average' => $subjectFinalAverage !== null ? round($subjectFinalAverage, 4) : null,
            'status' => $status,
        ]);
    }
}
