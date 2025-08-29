<?php

use App\Http\Controllers\AuthController;
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

// Public routes
Route::get('jobs', [JobController::class, 'index']);
Route::get('jobs/{id}', [JobController::class, 'show']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Job routes
    Route::post('jobs', [JobController::class, 'store'])->middleware('employer');
    Route::delete('jobs/{id}', [JobController::class, 'destroy'])->middleware('employer');
    
    // Application routes
    Route::post('applications', [ApplicationController::class, 'store'])->middleware('applicant');
    Route::get('applications/user/{userId}', [ApplicationController::class, 'userApplications']);
    Route::get('applications/job/{jobId}', [ApplicationController::class, 'jobApplications'])->middleware('employer');
    Route::patch('applications/{id}', [ApplicationController::class, 'updateStatus'])->middleware('employer');
    
    // Resume routes
    Route::prefix('resumes')->middleware('applicant')->group(function () {
        Route::get('/', [ResumeController::class, 'index']);
        Route::post('/upload', [ResumeController::class, 'upload']);
        Route::get('/{id}', [ResumeController::class, 'show']);
        Route::put('/{id}', [ResumeController::class, 'update']);
        Route::delete('/{id}', [ResumeController::class, 'destroy']);
        Route::get('/{id}/download', [ResumeController::class, 'download']);
    });

    // Keep the old routes for backward compatibility
    Route::post('resume/upload', [ResumeController::class, 'upload'])->middleware('applicant');
    Route::get('resume', [ResumeController::class, 'show'])->middleware('applicant');
    Route::get('resume/download', [ResumeController::class, 'download'])->middleware('applicant');
    
    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::get('admin/applications', function () {
            $applications = \App\Models\Application::with(['job.employer', 'user'])->latest()->get();
            return response()->json($applications);
        });
        
        Route::get('admin/users', function () {
            $users = \App\Models\User::withCount(['jobs', 'applications'])->latest()->get();
            return response()->json($users);
        });
        
        Route::get('admin/stats', function () {
            $stats = [
                'total_users' => \App\Models\User::count(),
                'total_employers' => \App\Models\User::where('role', 'employer')->count(),
                'total_applicants' => \App\Models\User::where('role', 'applicant')->count(),
                'total_jobs' => \App\Models\Job::count(),
                'total_applications' => \App\Models\Application::count(),
                'recent_jobs' => \App\Models\Job::with('employer')->latest()->take(5)->get(),
                'recent_applications' => \App\Models\Application::with(['job', 'user'])->latest()->take(5)->get(),
            ];
            return response()->json($stats);
        });
    });
});