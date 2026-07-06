<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GroupContributionController;
use App\Http\Controllers\ProjectSubmissionController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\TeamDocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// Public auth routes — rate limited to prevent brute-force
// throttle:5,1  → 5 requests per 1 minute per IP
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/login',          [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
});

// Register — separate, slightly higher limit (10/min) to allow usability
Route::middleware('throttle:10,1')->post('/register', [AuthController::class, 'register']);

// Logout requires auth
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// ─────────────────────────────────────────────────────────────────────────────
// Public route — no auth required, rate limited to 60/min
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('throttle:60,1')->get('/public/submissions', [ProjectSubmissionController::class, 'publicIndex']);

// ─────────────────────────────────────────────────────────────────────────────
// All protected routes — require Sanctum token and rate limited to 60/min
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    // Current authenticated user (minimal fields)
    Route::get('/user', function (Request $request) {
        $u = $request->user();
        return response()->json([
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
            'role'  => $u->role,
        ]);
    });

    // Project submissions
    Route::get('/submissions',                                    [ProjectSubmissionController::class, 'index']);
    Route::post('/submissions',                                   [ProjectSubmissionController::class, 'store']);
    Route::patch('/submissions/{submission}/review',              [ProjectSubmissionController::class, 'updateReview']);
    Route::patch('/submissions/{submission}/visibility',          [ProjectSubmissionController::class, 'updateVisibility']);
    Route::delete('/submissions/{submission}',                    [ProjectSubmissionController::class, 'destroy']);

    // Group contributions (linked to project submissions)
    Route::get('/contributions',                                  [GroupContributionController::class, 'indexAll']);
    Route::get('/submissions/{submission}/contributions',         [GroupContributionController::class, 'index']);
    Route::post('/submissions/{submission}/contributions',        [GroupContributionController::class, 'store']);
    Route::delete('/contributions/{contribution}',                [GroupContributionController::class, 'destroy']);

    // Team documents
    Route::get('/team-documents',                                 [TeamDocumentController::class, 'index']);
    Route::post('/team-documents',                                [TeamDocumentController::class, 'store']);
    Route::delete('/team-documents/{teamDocument}',               [TeamDocumentController::class, 'destroy']);

    // Project types
    Route::get('/project-types',                                  [ProjectTypeController::class, 'index']);
    Route::post('/project-types',                                 [ProjectTypeController::class, 'store']);

    // User/Member management — role checks enforced inside controllers
    Route::get('/members',                                        [UserController::class, 'index']);
    Route::post('/members',                                       [UserController::class, 'store']);
    Route::get('/members/{id}',                                   [UserController::class, 'show']);
    Route::put('/members/{id}',                                   [UserController::class, 'update']);
    Route::delete('/members/{id}',                                [UserController::class, 'destroy']);
    Route::post('/members/invite',                                [UserController::class, 'invite']);

    // Notifications
    Route::get('/notifications',                                  [NotificationController::class, 'index']);
    Route::post('/notifications/mark-all-read',                   [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read',                       [NotificationController::class, 'markAsRead']);

    // File uploads
    Route::post('/upload',                                        [UploadController::class, 'upload']);
});
