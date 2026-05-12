<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPerformance extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'attendance_percentage' => 'decimal:2',
        'test_scores' => 'decimal:2',
        'worksheet_completion' => 'decimal:2',
        'accuracy_percentage' => 'decimal:2',
        'speed_performance' => 'decimal:2',
        'homework_completion' => 'decimal:2',
        'instructor_rating' => 'decimal:2',
        'total_score' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    protected $fillable = [
        'student_id',
        'attendance_percentage',
        'test_scores',
        'worksheet_completion',
        'accuracy_percentage',
        'speed_performance',
        'homework_completion',
        'instructor_rating',
        'total_score',
        'calculated_at',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}