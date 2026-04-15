<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorksheetCompletion extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'worksheet_completions';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['student_id', 'worksheet_id', 'completed_at', 'score'];
    protected $casts = ['completed_at' => 'datetime'];
}

