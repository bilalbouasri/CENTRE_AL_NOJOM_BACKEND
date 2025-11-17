<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Get all teachers with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = Teacher::with(['subjects']);

        // Filter by name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by subject
        if ($request->has('subject_id') && $request->subject_id) {
            $query->whereHas('subjects', function($q) use ($request) {
                $q->where('subjects.id', $request->subject_id);
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $teachers = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $teachers->items(),
            'meta' => [
                'current_page' => $teachers->currentPage(),
                'last_page' => $teachers->lastPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
            ]
        ]);
    }

    /**
     * Get specific teacher
     */
    public function show($id)
    {
        $teacher = Teacher::with(['subjects'])->find($id);

        if (!$teacher) {
            return response()->json([
                'error' => [
                    'code' => 'TEACHER_NOT_FOUND',
                    'message' => 'Teacher not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        return response()->json([
            'data' => $teacher
        ]);
    }

    /**
     * Create new teacher
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'required|string|max:255',
            'experience_years' => 'required|integer|min:0',
            'hourly_rate' => 'required|numeric|min:0',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $validator->errors(),
                    'timestamp' => now()->toISOString(),
                ]
            ], 422);
        }

        try {
            $teacher = Teacher::create($request->only([
                'first_name', 'last_name', 'email', 'phone', 'address',
                'qualification', 'experience_years', 'hourly_rate', 'status'
            ]));

            // Attach subjects
            $teacher->subjects()->attach($request->subjects);

            return response()->json([
                'data' => $teacher->load(['subjects']),
                'message' => 'Teacher created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'CREATE_ERROR',
                    'message' => 'Failed to create teacher',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Update teacher
     */
    public function update(Request $request, $id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'error' => [
                    'code' => 'TEACHER_NOT_FOUND',
                    'message' => 'Teacher not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:teachers,email,' . $id,
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'sometimes|required|string|max:255',
            'experience_years' => 'sometimes|required|integer|min:0',
            'hourly_rate' => 'sometimes|required|numeric|min:0',
            'subjects' => 'sometimes|array',
            'subjects.*' => 'exists:subjects,id',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed',
                    'details' => $validator->errors(),
                    'timestamp' => now()->toISOString(),
                ]
            ], 422);
        }

        try {
            $teacher->update($request->only([
                'first_name', 'last_name', 'email', 'phone', 'address',
                'qualification', 'experience_years', 'hourly_rate', 'status'
            ]));

            // Update subjects if provided
            if ($request->has('subjects')) {
                $teacher->subjects()->sync($request->subjects);
            }

            return response()->json([
                'data' => $teacher->load(['subjects']),
                'message' => 'Teacher updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'UPDATE_ERROR',
                    'message' => 'Failed to update teacher',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Delete teacher
     */
    public function destroy($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json([
                'error' => [
                    'code' => 'TEACHER_NOT_FOUND',
                    'message' => 'Teacher not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        try {
            // Detach subjects first
            $teacher->subjects()->detach();
            $teacher->delete();

            return response()->json([
                'message' => 'Teacher deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DELETE_ERROR',
                    'message' => 'Failed to delete teacher',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get teacher statistics
     */
    public function statistics($id)
    {
        $teacher = Teacher::with(['subjects'])->find($id);

        if (!$teacher) {
            return response()->json([
                'error' => [
                    'code' => 'TEACHER_NOT_FOUND',
                    'message' => 'Teacher not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        // Get classes taught by this teacher
        $classesCount = $teacher->classes()->count();
        $activeClassesCount = $teacher->classes()->where('status', 'active')->count();

        // Get total students taught
        $totalStudents = $teacher->classes()->withCount('students')->get()->sum('students_count');

        return response()->json([
            'data' => [
                'teacher' => $teacher,
                'statistics' => [
                    'total_classes' => $classesCount,
                    'active_classes' => $activeClassesCount,
                    'total_students' => $totalStudents,
                ]
            ]
        ]);
    }
}