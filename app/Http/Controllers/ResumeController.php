<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResumeController extends Controller
{
    // List all resumes for the authenticated user
    public function index()
    {
        $resumes = Resume::where('user_id', auth()->id())->get();
        return response()->json($resumes);
    }
   
    // Handle resume upload and set as default
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Store the file
        $file = $request->file('resume');
        $path = $file->store('resumes');

        // If setting as default, remove default status from other resumes
        $isDefault = $request->input('is_default', true);
        if ($isDefault) {
            Resume::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        $resume = Resume::create([
            'user_id' => auth()->id(),
            'file_name' => $file->hashName(),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'resume' => $resume
        ], 201);
    }


    // View a specific resume or default resume
    public function show($id = null)
    {
        // If no ID provided, show default resume
        if (!$id) {
            $resume = Resume::where('user_id', auth()->id())
                ->where('is_default', true)
                ->first();
        } else {
            $resume = Resume::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();
        }
        
        if (!$resume) {
            return response()->json(['error' => 'Resume not found'], 404);
        }

        return response()->json($resume);
    }

    // Update resume (e.g., set as default)
    public function update(Request $request, $id)
    {
        $resume = Resume::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$resume) {
            return response()->json(['error' => 'Resume not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // If setting as default, remove default status from other resumes
        if ($request->has('is_default') && $request->is_default) {
            Resume::where('user_id', auth()->id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $resume->update($request->only('is_default'));

        return response()->json([
            'message' => 'Resume updated successfully',
            'resume' => $resume
        ]);
    }

    // Delete a resume
    public function destroy($id)
    {
        $resume = Resume::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();
        
        if (!$resume) {
            return response()->json(['error' => 'Resume not found'], 404);
        }

        // Prevent deletion if it's the only resume
        $resumeCount = Resume::where('user_id', auth()->id())->count();
        if ($resumeCount <= 1) {
            return response()->json(['error' => 'Cannot delete your only resume'], 400);
        }

        Storage::delete($resume->file_path);
        $resume->delete();

        // If the deleted resume was default, set another as default
        if ($resume->is_default) {
            $newDefault = Resume::where('user_id', auth()->id())->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return response()->json(['message' => 'Resume deleted successfully']);
    }
// Download resume file
    public function download($id = null)
    {
        // If no ID provided, download default resume
        if (!$id) {
            $resume = Resume::where('user_id', auth()->id())
                ->where('is_default', true)
                ->first();
        } else {
            $resume = Resume::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();
        }
        
        if (!$resume) {
            return response()->json(['error' => 'Resume not found'], 404);
        }

        return Storage::download($resume->file_path, $resume->original_name);
    }
}