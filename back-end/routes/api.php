<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProtectedController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\TodoController;
/*
|--------------------------------------------------------------------------
| API Routes
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 🔹 Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/protected-route', [ProtectedController::class, 'index']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // Subjects CRUD
    Route::apiResource('subjects', SubjectController::class);

    // Assessments CRUD
    Route::apiResource('assessments', AssessmentController::class);

    Route::apiResource('todos', TodoController::class);
});
