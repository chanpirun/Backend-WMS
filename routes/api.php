<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupContributionController;
use App\Http\Controllers\ProjectSubmissionController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\TeamDocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Public route — no auth required, returns only public submissions
Route::get('/public/submissions', [ProjectSubmissionController::class, 'publicIndex']);

// Project submissions
Route::middleware('auth:sanctum')->get('/submissions', [ProjectSubmissionController::class, 'index']);
Route::middleware('auth:sanctum')->post('/submissions', [ProjectSubmissionController::class, 'store']);
Route::middleware('auth:sanctum')->patch('/submissions/{submission}/review', [ProjectSubmissionController::class, 'updateReview']);
Route::middleware('auth:sanctum')->patch('/submissions/{submission}/visibility', [ProjectSubmissionController::class, 'updateVisibility']);
Route::middleware('auth:sanctum')->delete('/submissions/{submission}', [ProjectSubmissionController::class, 'destroy']);

// Group contributions (linked to project submissions)
Route::middleware('auth:sanctum')->get('/contributions', [GroupContributionController::class, 'indexAll']);
Route::middleware('auth:sanctum')->get('/submissions/{submission}/contributions', [GroupContributionController::class, 'index']);
Route::middleware('auth:sanctum')->post('/submissions/{submission}/contributions', [GroupContributionController::class, 'store']);
Route::middleware('auth:sanctum')->delete('/contributions/{contribution}', [GroupContributionController::class, 'destroy']);

// Team documents — standalone contributions, visible to submitter + tagged members
Route::middleware('auth:sanctum')->get('/team-documents', [TeamDocumentController::class, 'index']);
Route::middleware('auth:sanctum')->post('/team-documents', [TeamDocumentController::class, 'store']);
Route::middleware('auth:sanctum')->delete('/team-documents/{teamDocument}', [TeamDocumentController::class, 'destroy']);

// Project types (list + create custom)
Route::middleware('auth:sanctum')->get('/project-types', [ProjectTypeController::class, 'index']);
Route::middleware('auth:sanctum')->post('/project-types', [ProjectTypeController::class, 'store']);

// User/Member management endpoints (directors only)
Route::middleware('auth:sanctum')->get('/members', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->post('/members', [UserController::class, 'store']);
Route::middleware('auth:sanctum')->get('/members/{id}', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->put('/members/{id}', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/members/{id}', [UserController::class, 'destroy']);
Route::middleware('auth:sanctum')->post('/members/invite', [UserController::class, 'invite']);

// Notifications
Route::middleware('auth:sanctum')->get('/notifications', [NotificationController::class, 'index']);
Route::middleware('auth:sanctum')->post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
Route::middleware('auth:sanctum')->post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
