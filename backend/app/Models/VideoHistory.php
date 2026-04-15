<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoHistory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'video_history';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['student_id', 'video_id', 'watched_at', 'progress_percent'];

    protected $casts = ['watched_at' => 'datetime'];
}

