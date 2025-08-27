<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'profile']);
    });
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Job routes
    Route::apiResource('jobs', JobController::class)->except(['update']);
    
    // Application routes
    Route::prefix('applications')->group(function () {
        Route::post('/', [ApplicationController::class, 'store']);
        Route::get('/user/{userId}', [ApplicationController::class, 'userApplications']);
        Route::get('/job/{jobId}', [ApplicationController::class, 'jobApplications']);
        Route::patch('/{id}', [ApplicationController::class, 'updateStatus']);
    });
    
    // Resume routes
    Route::post('resume/upload', [ResumeController::class, 'upload']);
    Route::get('resume', [ResumeController::class, 'show']);
    Route::delete('resume', [ResumeController::class, 'destroy']);
});