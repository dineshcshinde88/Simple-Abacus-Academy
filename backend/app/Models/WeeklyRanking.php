<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyRanking extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'score' => 'decimal:2',
        'is_manual_override' => 'boolean',
    ];

    protected $fillable = [
        'student_id',
        'week_start',
        'week_end',
        'rank',
        'score',
        'is_manual_override',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}