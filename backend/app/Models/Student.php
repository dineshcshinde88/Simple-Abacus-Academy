<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'batches' => 'array',
        'subscription_start' => 'datetime',
        'subscription_end' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'tutor_id',
        'level_id',
        'batches',
        'subscription_plan',
        'subscription_start',
        'subscription_end',
        'subscription_status',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function tutor() { return $this->belongsTo(Tutor::class); }
    public function level() { return $this->belongsTo(Level::class); }
}

