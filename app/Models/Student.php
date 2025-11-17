<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'grade',
        'joined_date',
        'notes',
    ];

    protected $casts = [
        'joined_date' => 'date',
    ];

    /**
     * Get the subjects for the student.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subject');
    }

    /**
     * Get the classes for the student.
     */
    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_student');
    }

    /**
     * Get the payments for the student.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the monthly payments for the student.
     */
    public function monthlyPayments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the full name of the student.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}