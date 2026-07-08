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
});

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
});

// Register — separate, slightly higher limit (10/min) to allow usability
Route::middleware('throttle:10,1')->post('/register', [AuthController::class, 'register']);

// Diagnostic route for upload limits
Route::get('/debug-upload-limits', function () {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size'       => ini_get('post_max_size'),
        'memory_limit'        => ini_get('memory_limit'),
        'loaded_ini_file'     => php_ini_loaded_file(),
    ]);
});

// Route to programmatically run storage:link on the remote server
Route::get('/run-storage-link', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return response()->json([
            'message' => 'Storage link created successfully.',
            'output' => \Illuminate\Support\Facades\Artisan::output(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to create storage link.',
            'error' => $e->getMessage(),
        ], 500);
    }
});

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
