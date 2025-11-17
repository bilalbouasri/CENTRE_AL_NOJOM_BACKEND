<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    /**
     * Get all subjects with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = Subject::query();

        // Filter by name
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name_en', 'like', "%{$request->search}%")
                  ->orWhere('name_ar', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        // Filter by grade level
        if ($request->has('grade_level') && $request->grade_level) {
            $query->where('grade_level', $request->grade_level);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $subjects = $query->orderBy('name_en')->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $subjects->items(),
            'meta' => [
                'current_page' => $subjects->currentPage(),
                'last_page' => $subjects->lastPage(),
                'per_page' => $subjects->perPage(),
                'total' => $subjects->total(),
            ]
        ]);
    }

    /**
     * Get specific subject
     */
    public function show($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'error' => [
                    'code' => 'SUBJECT_NOT_FOUND',
                    'message' => 'Subject not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        return response()->json([
            'data' => $subject
        ]);
    }

    /**
     * Create new subject
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:subjects,code',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'grade_level' => 'required|string|in:7,8,9,10,11,12',
            'hours_per_week' => 'required|integer|min:1|max:20',
            'price_per_hour' => 'required|numeric|min:0',
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
            $subject = Subject::create($request->all());

            return response()->json([
                'data' => $subject,
                'message' => 'Subject created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'CREATE_ERROR',
                    'message' => 'Failed to create subject',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Update subject
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'error' => [
                    'code' => 'SUBJECT_NOT_FOUND',
                    'message' => 'Subject not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:50|unique:subjects,code,' . $id,
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'grade_level' => 'sometimes|required|string|in:7,8,9,10,11,12',
            'hours_per_week' => 'sometimes|required|integer|min:1|max:20',
            'price_per_hour' => 'sometimes|required|numeric|min:0',
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
            $subject->update($request->all());

            return response()->json([
                'data' => $subject,
                'message' => 'Subject updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'UPDATE_ERROR',
                    'message' => 'Failed to update subject',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Delete subject
     */
    public function destroy($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'error' => [
                    'code' => 'SUBJECT_NOT_FOUND',
                    'message' => 'Subject not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        try {
            // Check if subject is being used in any classes
            if ($subject->classes()->exists()) {
                return response()->json([
                    'error' => [
                        'code' => 'SUBJECT_IN_USE',
                        'message' => 'Cannot delete subject that is assigned to classes',
                        'timestamp' => now()->toISOString(),
                    ]
                ], 422);
            }

            $subject->delete();

            return response()->json([
                'message' => 'Subject deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DELETE_ERROR',
                    'message' => 'Failed to delete subject',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get subjects by grade level
     */
    public function byGrade($grade)
    {
        $subjects = Subject::where('grade_level', $grade)
                          ->where('status', 'active')
                          ->orderBy('name_en')
                          ->get();

        return response()->json([
            'data' => $subjects
        ]);
    }

    /**
     * Get subject statistics
     */
    public function statistics($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'error' => [
                    'code' => 'SUBJECT_NOT_FOUND',
                    'message' => 'Subject not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $classesCount = $subject->classes()->count();
        $activeClassesCount = $subject->classes()->where('status', 'active')->count();
        $teachersCount = $subject->teachers()->count();
        $studentsCount = $subject->classes()->withCount('students')->get()->sum('students_count');

        return response()->json([
            'data' => [
                'subject' => $subject,
                'statistics' => [
                    'total_classes' => $classesCount,
                    'active_classes' => $activeClassesCount,
                    'total_teachers' => $teachersCount,
                    'total_students' => $studentsCount,
                ]
            ]
        ]);
    }
}