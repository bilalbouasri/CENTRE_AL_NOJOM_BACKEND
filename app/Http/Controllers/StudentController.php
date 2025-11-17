<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Student::with(['subjects']);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Grade filter
        if ($request->has('grade') && $request->grade) {
            $query->where('grade', $request->grade);
        }

        // Payment status filter
        if ($request->has('payment_status') && $request->payment_status) {
            // This would require a more complex query with payment calculations
            // For now, we'll implement basic filtering
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = str_starts_with($sortField, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sortField, '-');
        
        if (in_array($sortField, ['first_name', 'last_name', 'grade', 'joined_date'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('limit', 30);
        $students = $query->paginate($perPage);

        // Calculate payment status for each student
        $students->getCollection()->transform(function ($student) {
            $student->payment_status = $this->calculatePaymentStatus($student);
            $student->total_subjects = $student->subjects->count();
            $student->paid_subjects = $this->calculatePaidSubjects($student);
            $student->unpaid_subjects = $student->total_subjects - $student->paid_subjects;
            return $student;
        });

        return response()->json([
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'total_pages' => $students->lastPage(),
                'total_count' => $students->total(),
                'per_page' => $students->perPage(),
            ],
            'filters' => [
                'grades' => ['7', '8', '9', '10', '11', '12'],
                'payment_statuses' => ['paid', 'partial', 'unpaid', 'no_subjects'],
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:students,phone',
            'grade' => 'required|string|in:7,8,9,10,11,12',
            'joined_date' => 'required|date',
            'notes' => 'nullable|string',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
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

        DB::beginTransaction();
        try {
            $student = Student::create($request->only([
                'first_name', 'last_name', 'phone', 'grade', 'joined_date', 'notes'
            ]));

            if ($request->has('subjects')) {
                $student->subjects()->sync($request->subjects);
            }

            DB::commit();

            return response()->json($student->load(['subjects']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to create student',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = Student::with(['subjects', 'classes', 'payments'])->find($id);

        if (!$student) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        // Calculate payment status and other details
        $student->payment_status = $this->calculatePaymentStatus($student);
        $student->monthly_payments = $this->getMonthlyPayments($student);

        return response()->json($student);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|unique:students,phone,' . $id,
            'grade' => 'required|string|in:7,8,9,10,11,12',
            'joined_date' => 'required|date',
            'notes' => 'nullable|string',
            'subjects' => 'nullable|array',
            'subjects.*' => 'exists:subjects,id',
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

        DB::beginTransaction();
        try {
            $student->update($request->only([
                'first_name', 'last_name', 'phone', 'grade', 'joined_date', 'notes'
            ]));

            if ($request->has('subjects')) {
                $student->subjects()->sync($request->subjects);
            }

            DB::commit();

            return response()->json($student->load(['subjects']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to update student',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        DB::beginTransaction();
        try {
            $student->subjects()->detach();
            $student->classes()->detach();
            $student->payments()->delete();
            $student->delete();

            DB::commit();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to delete student',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Add student to class
     */
    public function joinClass(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|exists:classes,id',
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

        $student = Student::find($id);
        if (!$student) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        try {
            $student->classes()->syncWithoutDetaching([$request->class_id]);
            return response()->json(['message' => 'Student added to class successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to add student to class',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Import students from JSON
     */
    public function import(Request $request)
    {
        // This would handle file upload and bulk import
        // For now, return a placeholder response
        return response()->json([
            'error' => [
                'code' => 'NOT_IMPLEMENTED',
                'message' => 'Import functionality not yet implemented',
                'timestamp' => now()->toISOString(),
            ]
        ], 501);
    }

    /**
     * Calculate payment status for a student
     */
    private function calculatePaymentStatus(Student $student): string
    {
        // Simplified implementation - would need actual payment logic
        $totalSubjects = $student->subjects->count();
        if ($totalSubjects === 0) {
            return 'no_subjects';
        }

        $paidSubjects = $this->calculatePaidSubjects($student);
        
        if ($paidSubjects === $totalSubjects) {
            return 'paid';
        } elseif ($paidSubjects > 0) {
            return 'partial';
        } else {
            return 'unpaid';
        }
    }

    /**
     * Calculate number of paid subjects for a student
     */
    private function calculatePaidSubjects(Student $student): int
    {
        // Simplified - would check actual payments for current month
        return 0; // Placeholder
    }

    /**
     * Get monthly payments for a student
     */
    private function getMonthlyPayments(Student $student)
    {
        // Simplified - would return actual payment data
        return [];
    }
}