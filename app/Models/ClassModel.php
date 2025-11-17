<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'teacher_id',
        'subject_id',
        'grade_levels',
    ];

    protected $casts = [
        'grade_levels' => 'array',
    ];

    /**
     * Get the teacher for the class.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the subject for the class.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the students for the class.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student');
    }

    /**
     * Get the schedules for the class.
     */
    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }
}