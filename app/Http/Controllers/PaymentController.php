<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Get all payments with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = Payment::with(['student', 'class']);

        // Filter by student
        if ($request->has('student_id') && $request->student_id) {
            $query->where('student_id', $request->student_id);
        }

        // Filter by class
        if ($request->has('class_id') && $request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        // Search by reference number
        if ($request->has('search') && $request->search) {
            $query->where('reference_number', 'like', "%{$request->search}%");
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ]);
    }

    /**
     * Get specific payment
     */
    public function show($id)
    {
        $payment = Payment::with(['student', 'class'])->find($id);

        if (!$payment) {
            return response()->json([
                'error' => [
                    'code' => 'PAYMENT_NOT_FOUND',
                    'message' => 'Payment not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        return response()->json([
            'data' => $payment
        ]);
    }

    /**
     * Create new payment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'class_id' => 'required|exists:classes,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,check,mobile_payment',
            'payment_date' => 'required|date',
            'month' => 'required|string|max:20',
            'year' => 'required|integer|min:2020|max:2030',
            'reference_number' => 'nullable|string|max:100|unique:payments,reference_number',
            'notes' => 'nullable|string',
            'status' => 'required|in:completed,pending,failed',
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
            // Generate reference number if not provided
            $referenceNumber = $request->reference_number ?? 'PAY-' . time() . '-' . rand(1000, 9999);

            $payment = Payment::create(array_merge($request->all(), [
                'reference_number' => $referenceNumber,
            ]));

            return response()->json([
                'data' => $payment->load(['student', 'class']),
                'message' => 'Payment created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'CREATE_ERROR',
                    'message' => 'Failed to create payment',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Update payment
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'error' => [
                    'code' => 'PAYMENT_NOT_FOUND',
                    'message' => 'Payment not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|required|exists:students,id',
            'class_id' => 'sometimes|required|exists:classes,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'sometimes|required|in:cash,bank_transfer,check,mobile_payment',
            'payment_date' => 'sometimes|required|date',
            'month' => 'sometimes|required|string|max:20',
            'year' => 'sometimes|required|integer|min:2020|max:2030',
            'reference_number' => 'sometimes|nullable|string|max:100|unique:payments,reference_number,' . $id,
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|in:completed,pending,failed',
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
            $payment->update($request->all());

            return response()->json([
                'data' => $payment->load(['student', 'class']),
                'message' => 'Payment updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'UPDATE_ERROR',
                    'message' => 'Failed to update payment',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Delete payment
     */
    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'error' => [
                    'code' => 'PAYMENT_NOT_FOUND',
                    'message' => 'Payment not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        try {
            $payment->delete();

            return response()->json([
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'DELETE_ERROR',
                    'message' => 'Failed to delete payment',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }

    /**
     * Get student payment history
     */
    public function studentPayments($studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json([
                'error' => [
                    'code' => 'STUDENT_NOT_FOUND',
                    'message' => 'Student not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $payments = Payment::with(['class'])
                          ->where('student_id', $studentId)
                          ->orderBy('payment_date', 'desc')
                          ->get();

        $totalPaid = $payments->where('status', 'completed')->sum('amount');
        $pendingPayments = $payments->where('status', 'pending');

        return response()->json([
            'data' => [
                'student' => $student,
                'payments' => $payments,
                'summary' => [
                    'total_paid' => $totalPaid,
                    'pending_payments_count' => $pendingPayments->count(),
                    'pending_amount' => $pendingPayments->sum('amount'),
                ]
            ]
        ]);
    }

    /**
     * Get class payment summary
     */
    public function classPayments($classId)
    {
        $class = ClassModel::with(['students'])->find($classId);

        if (!$class) {
            return response()->json([
                'error' => [
                    'code' => 'CLASS_NOT_FOUND',
                    'message' => 'Class not found',
                    'timestamp' => now()->toISOString(),
                ]
            ], 404);
        }

        $payments = Payment::with(['student'])
                          ->where('class_id', $classId)
                          ->orderBy('payment_date', 'desc')
                          ->get();

        $totalCollected = $payments->where('status', 'completed')->sum('amount');
        $expectedRevenue = $class->students()->count() * $class->monthly_fee;
        $collectionRate = $expectedRevenue > 0 ? ($totalCollected / $expectedRevenue) * 100 : 0;

        return response()->json([
            'data' => [
                'class' => $class,
                'payments' => $payments,
                'summary' => [
                    'total_collected' => $totalCollected,
                    'expected_revenue' => $expectedRevenue,
                    'collection_rate' => round($collectionRate, 2),
                    'total_students' => $class->students()->count(),
                ]
            ]
        ]);
    }

    /**
     * Get payment statistics
     */
    public function statistics(Request $request)
    {
        $query = Payment::query();

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $totalPayments = $query->count();
        $completedPayments = $query->clone()->where('status', 'completed')->count();
        $pendingPayments = $query->clone()->where('status', 'pending')->count();
        $totalAmount = $query->clone()->where('status', 'completed')->sum('amount');

        // Payment methods breakdown
        $paymentMethods = $query->clone()
                               ->where('status', 'completed')
                               ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
                               ->groupBy('payment_method')
                               ->get();

        // Monthly revenue
        $monthlyRevenue = $query->clone()
                               ->where('status', 'completed')
                               ->selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(amount) as total')
                               ->groupBy('year', 'month')
                               ->orderBy('year', 'desc')
                               ->orderBy('month', 'desc')
                               ->limit(6)
                               ->get();

        return response()->json([
            'data' => [
                'total_payments' => $totalPayments,
                'completed_payments' => $completedPayments,
                'pending_payments' => $pendingPayments,
                'total_amount' => $totalAmount,
                'payment_methods' => $paymentMethods,
                'monthly_revenue' => $monthlyRevenue,
            ]
        ]);
    }
}