<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UtilityController extends Controller
{
    /**
     * Get available grade levels
     */
    public function grades()
    {
        return response()->json([
            'grades' => ['7', '8', '9', '10', '11', '12']
        ]);
    }

    /**
     * Get available payment methods
     */
    public function paymentMethods()
    {
        return response()->json([
            'payment_methods' => ['cash', 'bank_transfer', 'check', 'mobile_payment']
        ]);
    }

    /**
     * Get week days for scheduling
     */
    public function weekDays()
    {
        return response()->json([
            'days' => [
                ['value' => 'monday', 'label_en' => 'Monday', 'label_ar' => 'الإثنين'],
                ['value' => 'tuesday', 'label_en' => 'Tuesday', 'label_ar' => 'الثلاثاء'],
                ['value' => 'wednesday', 'label_en' => 'Wednesday', 'label_ar' => 'الأربعاء'],
                ['value' => 'thursday', 'label_en' => 'Thursday', 'label_ar' => 'الخميس'],
                ['value' => 'friday', 'label_en' => 'Friday', 'label_ar' => 'الجمعة'],
                ['value' => 'saturday', 'label_en' => 'Saturday', 'label_ar' => 'السبت'],
                ['value' => 'sunday', 'label_en' => 'Sunday', 'label_ar' => 'الأحد'],
            ]
        ]);
    }

    /**
     * Handle file upload
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|string|in:student_photo,document,other',
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
            $file = $request->file('file');
            $type = $request->type;
            
            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = $type . '_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Store file (in production, you'd use cloud storage)
            $path = $file->storeAs('uploads/' . $type, $filename, 'public');

            return response()->json([
                'file_url' => asset('storage/' . $path),
                'file_name' => $filename,
                'file_size' => $file->getSize(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'UPLOAD_ERROR',
                    'message' => 'Failed to upload file',
                    'timestamp' => now()->toISOString(),
                ]
            ], 500);
        }
    }
}