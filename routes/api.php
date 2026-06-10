<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectSubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/submissions', [ProjectSubmissionController::class, 'index']);
Route::middleware('auth:sanctum')->post('/submissions', [ProjectSubmissionController::class, 'store']);
Route::middleware('auth:sanctum')->patch('/submissions/{submission}/review', [ProjectSubmissionController::class, 'updateReview']);
Route::middleware('auth:sanctum')->patch('/submissions/{submission}/visibility', [ProjectSubmissionController::class, 'updateVisibility']);
Route::middleware('auth:sanctum')->delete('/submissions/{submission}', [ProjectSubmissionController::class, 'destroy']);

// User/Member management endpoints (directors only)
Route::middleware('auth:sanctum')->get('/members', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->post('/members', [UserController::class, 'store']);
Route::middleware('auth:sanctum')->get('/members/{id}', [UserController::class, 'show']);
Route::middleware('auth:sanctum')->put('/members/{id}', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/members/{id}', [UserController::class, 'destroy']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
