<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UtilityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Dashboard
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics']);
    
    // Students
    Route::apiResource('students', StudentController::class);
    Route::get('/students/{id}/payments', [StudentController::class, 'paymentHistory']);
    Route::get('/students/{id}/classes', [StudentController::class, 'classHistory']);
    
    // Teachers
    Route::apiResource('teachers', TeacherController::class);
    Route::get('/teachers/{id}/statistics', [TeacherController::class, 'statistics']);
    
    // Subjects
    Route::apiResource('subjects', SubjectController::class);
    Route::get('/subjects/grade/{grade}', [SubjectController::class, 'byGrade']);
    Route::get('/subjects/{id}/statistics', [SubjectController::class, 'statistics']);
    
    // Classes
    Route::apiResource('classes', ClassController::class);
    Route::post('/classes/{id}/students', [ClassController::class, 'addStudent']);
    Route::delete('/classes/{id}/students', [ClassController::class, 'removeStudent']);
    Route::get('/classes/{id}/statistics', [ClassController::class, 'statistics']);
    
    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::get('/payments/student/{studentId}', [PaymentController::class, 'studentPayments']);
    Route::get('/payments/class/{classId}', [PaymentController::class, 'classPayments']);
    Route::get('/payments/statistics', [PaymentController::class, 'statistics']);
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/financial-summary', [ReportController::class, 'financialSummary']);
        Route::get('/student-enrollment', [ReportController::class, 'studentEnrollment']);
        Route::get('/class-performance', [ReportController::class, 'classPerformance']);
        Route::get('/teacher-performance', [ReportController::class, 'teacherPerformance']);
        Route::get('/attendance', [ReportController::class, 'attendanceReport']);
    });
    
    // Utilities
    Route::prefix('utilities')->group(function () {
        Route::get('/grades', [UtilityController::class, 'grades']);
        Route::get('/payment-methods', [UtilityController::class, 'paymentMethods']);
        Route::get('/week-days', [UtilityController::class, 'weekDays']);
        Route::post('/upload', [UtilityController::class, 'upload']);
    });
});