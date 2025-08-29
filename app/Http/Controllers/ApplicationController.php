<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:jobs,id',
            'cover_letter' => 'nullable|string',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'resume_id' => 'nullable|exists:resumes,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Check if user has already applied for this job
        $existingApplication = Application::where('job_id', $request->job_id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingApplication) {
            return response()->json(['error' => 'You have already applied for this job'], 400);
        }

        // Check if job exists and is still open
        $job = Job::find($request->job_id);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        if (now()->gt($job->application_deadline)) {
            return response()->json(['error' => 'Application deadline has passed'], 400);
        }

        $resumeId = null;

        // Handle resume upload if provided
        if ($request->hasFile('resume')) {
            $resume = $this->uploadResume($request->file('resume'));
            $resumeId = $resume->id;
        } 
        // Use existing resume if specified
        elseif ($request->has('resume_id')) {
            $resume = Resume::where('id', $request->resume_id)
                ->where('user_id', auth()->id())
                ->first();
                
            if (!$resume) {
                return response()->json(['error' => 'Resume not found or unauthorized'], 404);
            }
            $resumeId = $resume->id;
        }
        // Use default resume if available
        else {
            $defaultResume = Resume::where('user_id', auth()->id())
                ->where('is_default', true)
                ->first();
                
            if ($defaultResume) {
                $resumeId = $defaultResume->id;
            }
        }

        $application = Application::create([
            'job_id' => $request->job_id,
            'user_id' => auth()->id(),
            'resume_id' => $resumeId,
            'cover_letter' => $request->cover_letter,
            'status' => 'applied',
        ]);

        return response()->json([
            'message' => 'Application submitted successfully',
            'application' => $application->load('job', 'resume')
        ], 201);
    }

    private function uploadResume($file)
    {
        // Delete existing default resume if any
        $existingResume = Resume::where('user_id', auth()->id())
            ->where('is_default', true)
            ->first();
            
        if ($existingResume) {
            Storage::delete($existingResume->file_path);
            $existingResume->delete();
        }

        // Store the new file
        $path = $file->store('resumes');

        return Resume::create([
            'user_id' => auth()->id(),
            'file_name' => $file->hashName(),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'is_default' => true,
        ]);
    }

    public function userApplications($userId)
    {
        // Users can only view their own applications, admins can view all
        if (auth()->id() != $userId && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $applications = Application::with(['job.employer', 'resume'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return response()->json($applications);
    }

    public function jobApplications($jobId)
    {
        $job = Job::find($jobId);
        
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        // Only job creator or admin can view applications for a job
        if (auth()->id() != $job->created_by && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $applications = Application::with(['user', 'resume'])
            ->where('job_id', $jobId)
            ->latest()
            ->get();

        return response()->json($applications);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:applied,shortlisted,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $application = Application::with('job')->find($id);
        
        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        // Only job creator or admin can update application status
        if (auth()->id() != $application->job->created_by && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $application->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application->load('user', 'job', 'resume')
        ]);
    }
}