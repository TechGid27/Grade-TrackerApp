<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProtectedController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\TodoController;
use App\Models\Assessment;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Public + Protected API endpoints
|--------------------------------------------------------------------------
*/

// -----------------------------------------
// 🔓 PUBLIC AUTH ROUTES
// -----------------------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);


// -----------------------------------------
// 🔒 PROTECTED ROUTES (SANCTUM AUTH)
// -----------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    // User session
    Route::get('/user', fn(Request $req) => $req->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // Protected example endpoint
    Route::get('/protected-route', [ProtectedController::class, 'index']);


    // -----------------------------------------
    // 📘 SUBJECTS CRUD (REST)
    // -----------------------------------------
    Route::apiResource('subjects', SubjectController::class);


    // -----------------------------------------
    // 📝 ASSESSMENTS CRUD (REST)
    // -----------------------------------------
    Route::apiResource('assessments', AssessmentController::class);


    // -----------------------------------------
    // 🎯 ASSESSMENT CALCULATIONS (Clean Grouping)
    // -----------------------------------------

    // Activity-based percentages
    Route::prefix('assessment')->group(function () {

        Route::get('quiz/{quarter}/{subject}',       [AssessmentController::class, 'getQuizPercent']);
        Route::get('exam/{quarter}/{subject}',       [AssessmentController::class, 'getExamPercent']);
        Route::get('assignment/{quarter}/{subject}', [AssessmentController::class, 'getAssignmentPercent']);
        Route::get('project/{quarter}/{subject}',    [AssessmentController::class, 'getProjectPercent']);

        route::get('allactivity/{quarter}/{subject}', [AssessmentController::class, 'getallActivitySpecificQuarter']);

        // Total percentage for a specific subject + quarter
        Route::get('total/{quarter}/{subject}',      [AssessmentController::class, 'getAllTotalPercentages']);

        Route::get('grade/{subject}', [AssessmentController::class, 'getSpecificGrade']);
        // overall

        // Grades across all subjects but ONLY for one quarter
        Route::get('grade/{quarter}',                [AssessmentController::class, 'getOverallGrades']);

        // Final grade across ALL quarters + ALL subjects
        Route::get('final_grade',                    [AssessmentController::class, 'getOverallGradesAllQuarters']);
    });


    // -----------------------------------------
    // 📌 TODO CRUD (REST)
    // -----------------------------------------
    Route::apiResource('todos', TodoController::class);

});
