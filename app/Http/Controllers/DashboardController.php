<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics and data
     */
    public function index()
    {
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        $totalClasses = ClassModel::count();
        $totalSubjects = Subject::count();

        // Calculate current month revenue
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currentMonthRevenue = Payment::where('payment_month', $currentMonth)
            ->where('payment_year', $currentYear)
            ->sum('amount');

        // Calculate monthly revenue (last 6 months)
        $last6MonthsRevenue = $this->getLast6MonthsRevenue();

        // Calculate year-to-date statistics
        $yearToDateStats = $this->getYearToDateStats();

        // Get recent payments
        $recentPayments = Payment::with(['student', 'subject'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'student_id' => $payment->student_id,
                    'student_name' => $payment->student->first_name . ' ' . $payment->student->last_name,
                    'amount' => $payment->amount,
                    'payment_month' => $payment->payment_month,
                    'payment_year' => $payment->payment_year,
                    'payment_date' => $payment->payment_date,
                    'subject_name' => $payment->subject->name_en,
                ];
            });

        return response()->json([
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_classes' => $totalClasses,
            'total_subjects' => $totalSubjects,
            'monthly_revenue' => $currentMonthRevenue,
            'current_month_revenue' => $currentMonthRevenue,
            'last_6_months_revenue' => $last6MonthsRevenue,
            'year_to_date' => $yearToDateStats,
            'recent_payments' => $recentPayments,
        ]);
    }

    /**
     * Get revenue data for the last 6 months
     */
    private function getLast6MonthsRevenue()
    {
        $revenueData = [];
        $currentMonth = now()->month;
        $currentYear = now()->year;

        for ($i = 5; $i >= 0; $i--) {
            $month = $currentMonth - $i;
            $year = $currentYear;

            if ($month <= 0) {
                $month += 12;
                $year -= 1;
            }

            $revenue = Payment::where('payment_month', $month)
                ->where('payment_year', $year)
                ->sum('amount');

            $monthName = date('F', mktime(0, 0, 0, $month, 1));

            $revenueData[] = [
                'month' => $monthName,
                'revenue' => $revenue,
            ];
        }

        return $revenueData;
    }

    /**
     * Get year-to-date statistics
     */
    private function getYearToDateStats()
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $totalRevenue = Payment::where('payment_year', $currentYear)
            ->sum('amount');

        $averageMonthly = $currentMonth > 0 ? $totalRevenue / $currentMonth : 0;

        // Calculate growth percentage (simplified - would compare with previous year)
        $previousYearRevenue = Payment::where('payment_year', $currentYear - 1)
            ->sum('amount');

        $growthPercentage = $previousYearRevenue > 0 
            ? (($totalRevenue - $previousYearRevenue) / $previousYearRevenue) * 100 
            : 0;

        return [
            'total_revenue' => $totalRevenue,
            'average_monthly' => $averageMonthly,
            'growth_percentage' => $growthPercentage,
        ];
    }
}