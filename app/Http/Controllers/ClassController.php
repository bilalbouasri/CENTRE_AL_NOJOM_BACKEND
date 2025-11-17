<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassController extends Controller
{
    /**
     * Get all classes with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = ClassModel::with(['subject', 'teacher', 'students']);

        // Filter by subject
        if ($request->has('subject_id') && $request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        // Filter by teacher
        if ($request->has('teacher_id') && $request->teacher_id) {
            $query->where('teacher_id', $request->teacher_id);
        }

        // Filter by grade level
        if ($request->has('grade_level') && $request->grade_level) {
            $query->where('grade_level', $request->grade_level);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $classes = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $classes->items(),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ]
        ]);
    }

    /**
     * Get specific class
     */
    public function show($id)
    {
        $class = ClassModel::with(['subject', 'teacher', 'students'])->find($id);

        if (!$class) {
            return response()->json([
                'error' => [
                    'code' => 'CLASS_NOT_FOUND',
                    'message' => 'Class not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        return response()->json([
            'data' => $class
        ]);
    }

    /**
     * Create new class
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'grade_level' => 'required|string|in:7,8,9,10,11,12',
            'schedule_days' => 'required|array',
            'schedule_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_students' => 'required|integer|min:1|max:30',
            'monthly_fee' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,completed',
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
            $class = ClassModel::create($request->all());

            return response()->json([
                'data' => $class->load(['subject', 'teacher']),
                'message' => 'Class created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'CREATE_ERROR',
                    'message' => 'Failed to create class',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Update class
     */
    public function update(Request $request, $id)
    {
        $class = ClassModel::find($id);

        if (!$class) {
            return response()->json([
                'error' => [
                    'code' => 'CLASS_NOT_FOUND',
                    'message' => 'Class not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'teacher_id' => 'sometimes|required|exists:teachers,id',
            'grade_level' => 'sometimes|required|string|in:7,8,9,10,11,12',
            'schedule_days' => 'sometimes|required|array',
            'schedule_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'max_students' => 'sometimes|required|integer|min:1|max:30',
            'monthly_fee' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:active,inactive,completed',
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
            $class->update($request->all());

            return response()->json([
                'data' => $class->load(['subject', 'teacher']),
                'message' => 'Class updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'UPDATE_ERROR',
                    'message' => 'Failed to update class',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Delete class
     */
    public function destroy($id)
    {
        $class = ClassModel::find($id);

        if (!$class) {
            return response()->json([
                'error' => [
                    'code' => 'CLASS_NOT_FOUND',
                    'message' => 'Class not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        try {
            // Check if class has students enrolled
            if ($class->students()->exists()) {
                return response()->json([
                    'error' => [
                        'code' => 'CLASS_HAS_STUDENTS',
                        'message' => 'Cannot delete class that has enrolled students',
                        'timestamp' => now()->toISOString(),
                    ]
                ], 422);
            }

            $class->delete();

            return response()->json([
                'message' => 'Class deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DELETE_ERROR',
                    'message' => 'Failed to delete class',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Add student to class
     */
    public function addStudent(Request $request, $id)
    {
        $class = ClassModel::find($id);

        if (!$class) {
            return response()->json([
                'error' => [
                    'code' => 'CLASS_NOT_FOUND',
                    'message' => 'Class not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
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
            // Check if student is already enrolled
            if ($class->students()->where('student_id', $request->student_id)->exists()) {
                return response()->json([
                    'error' => [
                        'code' => 'STUDENT_ALREADY_ENROLLED',
                        'message' => 'Student is already enrolled in this class',
                        'timestamp' => now()->toISOString(),
                    ]
                ], 422);
            }

            // Check if class is full
            if ($class->students()->count() >= $class->max_students) {
                return response()->json([
                    'error' => [
                        'code' => 'CLASS_FULL',
                        'message' => 'Class has reached maximum capacity',
                        'timestamp' => now()->toISOString(),
                    ]
                ], 422);
            }

            $class->students()->attach($request->student_id);

            return response()->json([
                'message' => 'Student added to class successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'ENROLLMENT_ERROR',
                    'message' => 'Failed to add student to class',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Remove student from class
     */
    public function removeStudent(Request $request, $id)
    {
        $class = ClassModel::find($id);

        if (!$class) {
            return response()->json([
                'error' => [
                    'code' => 'CLASS_NOT_FOUND',
                    'message' => 'Class not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
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
            $class->students()->detach($request->student_id);

            return response()->json([
                'message' => 'Student removed from class successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REMOVAL_ERROR',
                    'message' => 'Failed to remove student from class',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get class statistics
     */
    public function statistics($id)
    {
        $class = ClassModel::with(['subject', 'teacher', 'students'])->find($id);

        if (!$class) {
            return response()->json([
                'error' => [
                    'code' => 'CLASS_NOT_FOUND',
                    'message' => 'Class not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $studentsCount = $class->students()->count();
        $availableSlots = $class->max_students - $studentsCount;
        $attendanceRate = $class->students()->avg('attendance_rate') ?? 0;

        return response()->json([
            'data' => [
                'class' => $class,
                'statistics' => [
                    'total_students' => $studentsCount,
                    'available_slots' => $availableSlots,
                    'attendance_rate' => round($attendanceRate, 2),
                    'capacity_percentage' => round(($studentsCount / $class->max_students) * 100, 2),
                ]
            ]
        ]);
    }
}