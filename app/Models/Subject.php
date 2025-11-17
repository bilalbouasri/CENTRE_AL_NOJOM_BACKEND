<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'description',
        'fee_amount',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
    ];

    /**
     * Get the students for the subject.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_subject');
    }

    /**
     * Get the teachers for the subject.
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subject');
    }

    /**
     * Get the classes for the subject.
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    /**
     * Get the payments for the subject.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}