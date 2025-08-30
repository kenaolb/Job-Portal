<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    // List all jobs
    public function index()
    {
        $jobs = Job::with('employer')->latest()->get();
        return response()->json($jobs);
    }

    // Create a new job posting
    public function store(Request $request)
    {
        // Check if user is employer or admin
        if (!auth()->user()->isEmployer() && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized. Only employers can create jobs.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'location' => 'required|string|max:255',
            'type' => 'required|in:full-time,part-time,contract,freelance',
            'salary' => 'nullable|numeric|min:0',
            'application_deadline' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $job = Job::create([
            'title' => $request->title,
            'description' => $request->description,
            'requirements' => $request->requirements,
            'location' => $request->location,
            'type' => $request->type,
            'salary' => $request->salary,
            'application_deadline' => $request->application_deadline,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Job created successfully',
            'job' => $job->load('employer')
        ], 201);
    }

    // View a specific job
    public function show($id)
    {
        $job = Job::with('employer')->find($id);
        
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json($job);
    }
    // Delete a job posting
    public function destroy($id)
    {
        $job = Job::find($id);
        
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        // Check if the authenticated user is the job creator or admin
        if (auth()->id() !== $job->created_by && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized. You can only delete your own jobs.'], 403);
        }

        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }
}