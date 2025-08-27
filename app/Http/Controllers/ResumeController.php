<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResumeController extends Controller
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Delete existing resume if any
        $existingResume = Resume::where('user_id', auth()->id())->first();
        if ($existingResume) {
            Storage::delete($existingResume->file_path);
            $existingResume->delete();
        }

        // Store the file
        $file = $request->file('resume');
        $path = $file->store('resumes');

        $resume = Resume::create([
            'user_id' => auth()->id(),
            'file_name' => $file->hashName(),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'resume' => $resume
        ], 201);
    }

    public function show()
    {
        $resume = Resume::where('user_id', auth()->id())->first();
        
        if (!$resume) {
            return response()->json(['error' => 'Resume not found'], 404);
        }

        return response()->json($resume);
    }

    public function destroy()
    {
        $resume = Resume::where('user_id', auth()->id())->first();
        
        if (!$resume) {
            return response()->json(['error' => 'Resume not found'], 404);
        }

        Storage::delete($resume->file_path);
        $resume->delete();

        return response()->json(['message' => 'Resume deleted successfully']);
    }

    public function download()
    {
        $resume = Resume::where('user_id', auth()->id())->first();
        
        if (!$resume) {
            return response()->json(['error' => 'Resume not found'], 404);
        }

        return Storage::download($resume->file_path, $resume->original_name);
    }
}