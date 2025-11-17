<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Get financial summary report
     */
    public function financialSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Total revenue
            $totalRevenue = Payment::where('status', 'completed')
                                 ->whereBetween('payment_date', [$startDate, $endDate])
                                 ->sum('amount');

            // Revenue by payment method
            $revenueByMethod = Payment::where('status', 'completed')
                                    ->whereBetween('payment_date', [$startDate, $endDate])
                                    ->select('payment_method', DB::raw('SUM(amount) as total'))
                                    ->groupBy('payment_method')
                                    ->get();

            // Revenue by class
            $revenueByClass = Payment::with(['class'])
                                   ->where('status', 'completed')
                                   ->whereBetween('payment_date', [$startDate, $endDate])
                                   ->select('class_id', DB::raw('SUM(amount) as total'))
                                   ->groupBy('class_id')
                                   ->orderBy('total', 'desc')
                                   ->get();

            // Monthly revenue trend
            $monthlyTrend = Payment::where('status', 'completed')
                                 ->whereBetween('payment_date', [$startDate, $endDate])
                                 ->select(
                                     DB::raw('YEAR(payment_date) as year'),
                                     DB::raw('MONTH(payment_date) as month'),
                                     DB::raw('SUM(amount) as total')
                                 )
                                 ->groupBy('year', 'month')
                                 ->orderBy('year')
                                 ->orderBy('month')
                                 ->get();

            return response()->json([
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'total_revenue' => $totalRevenue,
                    'revenue_by_method' => $revenueByMethod,
                    'revenue_by_class' => $revenueByClass,
                    'monthly_trend' => $monthlyTrend,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REPORT_ERROR',
                    'message' => 'Failed to generate financial report',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get student enrollment report
     */
    public function studentEnrollment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Total students
            $totalStudents = Student::count();

            // New students in period
            $newStudents = Student::whereBetween('created_at', [$startDate, $endDate])->count();

            // Students by grade level
            $studentsByGrade = Student::select('grade_level', DB::raw('COUNT(*) as count'))
                                    ->groupBy('grade_level')
                                    ->orderBy('grade_level')
                                    ->get();

            // Students by status
            $studentsByStatus = Student::select('status', DB::raw('COUNT(*) as count'))
                                     ->groupBy('status')
                                     ->get();

            // Enrollment trend
            $enrollmentTrend = Student::select(
                                    DB::raw('YEAR(created_at) as year'),
                                    DB::raw('MONTH(created_at) as month'),
                                    DB::raw('COUNT(*) as count')
                                )
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->groupBy('year', 'month')
                                ->orderBy('year')
                                ->orderBy('month')
                                ->get();

            return response()->json([
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'total_students' => $totalStudents,
                    'new_students' => $newStudents,
                    'students_by_grade' => $studentsByGrade,
                    'students_by_status' => $studentsByStatus,
                    'enrollment_trend' => $enrollmentTrend,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REPORT_ERROR',
                    'message' => 'Failed to generate student enrollment report',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get class performance report
     */
    public function classPerformance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Total classes
            $totalClasses = ClassModel::count();

            // Classes by status
            $classesByStatus = ClassModel::select('status', DB::raw('COUNT(*) as count'))
                                       ->groupBy('status')
                                       ->get();

            // Classes by subject
            $classesBySubject = ClassModel::with(['subject'])
                                        ->select('subject_id', DB::raw('COUNT(*) as count'))
                                        ->groupBy('subject_id')
                                        ->orderBy('count', 'desc')
                                        ->get();

            // Class capacity utilization
            $capacityUtilization = ClassModel::select(
                                            'id',
                                            'name',
                                            'max_students',
                                            DB::raw('(SELECT COUNT(*) FROM class_student WHERE class_student.class_id = classes.id) as current_students'),
                                            DB::raw('ROUND(((SELECT COUNT(*) FROM class_student WHERE class_student.class_id = classes.id) / max_students) * 100, 2) as utilization_rate')
                                        )
                                        ->having('utilization_rate', '>', 0)
                                        ->orderBy('utilization_rate', 'desc')
                                        ->limit(10)
                                        ->get();

            // New classes in period
            $newClasses = ClassModel::whereBetween('created_at', [$startDate, $endDate])->count();

            return response()->json([
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'total_classes' => $totalClasses,
                    'new_classes' => $newClasses,
                    'classes_by_status' => $classesByStatus,
                    'classes_by_subject' => $classesBySubject,
                    'capacity_utilization' => $capacityUtilization,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REPORT_ERROR',
                    'message' => 'Failed to generate class performance report',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get teacher performance report
     */
    public function teacherPerformance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Total teachers
            $totalTeachers = Teacher::count();

            // Teachers by status
            $teachersByStatus = Teacher::select('status', DB::raw('COUNT(*) as count'))
                                     ->groupBy('status')
                                     ->get();

            // Top performing teachers (by number of classes)
            $topTeachers = Teacher::withCount(['classes' => function($query) use ($startDate, $endDate) {
                                    $query->whereBetween('created_at', [$startDate, $endDate]);
                                }])
                                ->with(['subjects'])
                                ->orderBy('classes_count', 'desc')
                                ->limit(10)
                                ->get();

            // Teacher subject distribution
            $teacherSubjectDistribution = DB::table('teacher_subject')
                                          ->join('teachers', 'teacher_subject.teacher_id', '=', 'teachers.id')
                                          ->join('subjects', 'teacher_subject.subject_id', '=', 'subjects.id')
                                          ->select('subjects.name_en as subject_name', DB::raw('COUNT(*) as teacher_count'))
                                          ->groupBy('subjects.id', 'subjects.name_en')
                                          ->orderBy('teacher_count', 'desc')
                                          ->get();

            // New teachers in period
            $newTeachers = Teacher::whereBetween('created_at', [$startDate, $endDate])->count();

            return response()->json([
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'total_teachers' => $totalTeachers,
                    'new_teachers' => $newTeachers,
                    'teachers_by_status' => $teachersByStatus,
                    'top_teachers' => $topTeachers,
                    'teacher_subject_distribution' => $teacherSubjectDistribution,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REPORT_ERROR',
                    'message' => 'Failed to generate teacher performance report',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get attendance report
     */
    public function attendanceReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // Overall attendance statistics
            $overallAttendance = Student::select(
                                        DB::raw('AVG(attendance_rate) as average_attendance'),
                                        DB::raw('COUNT(*) as total_students'),
                                        DB::raw('SUM(CASE WHEN attendance_rate >= 80 THEN 1 ELSE 0 END) as good_attendance'),
                                        DB::raw('SUM(CASE WHEN attendance_rate < 80 AND attendance_rate >= 60 THEN 1 ELSE 0 END) as average_attendance_count'),
                                        DB::raw('SUM(CASE WHEN attendance_rate < 60 THEN 1 ELSE 0 END) as poor_attendance')
                                    )
                                    ->first();

            // Attendance by grade level
            $attendanceByGrade = Student::select(
                                        'grade_level',
                                        DB::raw('AVG(attendance_rate) as average_attendance'),
                                        DB::raw('COUNT(*) as student_count')
                                    )
                                    ->groupBy('grade_level')
                                    ->orderBy('grade_level')
                                    ->get();

            // Students with poor attendance
            $poorAttendanceStudents = Student::where('attendance_rate', '<', 60)
                                           ->orderBy('attendance_rate')
                                           ->limit(20)
                                           ->get(['id', 'first_name', 'last_name', 'grade_level', 'attendance_rate']);

            return response()->json([
                'data' => [
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                    'overall_attendance' => $overallAttendance,
                    'attendance_by_grade' => $attendanceByGrade,
                    'poor_attendance_students' => $poorAttendanceStudents,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'REPORT_ERROR',
                    'message' => 'Failed to generate attendance report',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }
}