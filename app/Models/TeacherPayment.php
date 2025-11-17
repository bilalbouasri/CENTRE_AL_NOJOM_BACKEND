<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'amount',
        'payment_month',
        'payment_year',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the teacher for the payment.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}