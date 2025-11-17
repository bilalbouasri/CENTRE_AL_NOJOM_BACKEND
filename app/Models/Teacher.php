<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'joined_date',
        'monthly_percentage',
        'notes',
    ];

    protected $casts = [
        'joined_date' => 'date',
        'monthly_percentage' => 'decimal:2',
    ];

    /**
     * Get the subjects for the teacher.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject');
    }

    /**
     * Get the classes for the teacher.
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    /**
     * Get the payments for the teacher.
     */
    public function payments()
    {
        return $this->hasMany(TeacherPayment::class);
    }

    /**
     * Get the full name of the teacher.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}