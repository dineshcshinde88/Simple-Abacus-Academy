<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchievementBadge extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'awarded_at' => 'datetime',
    ];

    protected $fillable = [
        'student_id',
        'badge_type',
        'badge_name',
        'description',
        'awarded_at',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}