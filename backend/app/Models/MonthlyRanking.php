<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyRanking extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'month_start' => 'date',
        'month_end' => 'date',
        'score' => 'decimal:2',
        'is_manual_override' => 'boolean',
    ];

    protected $fillable = [
        'student_id',
        'month_start',
        'month_end',
        'rank',
        'score',
        'is_manual_override',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}