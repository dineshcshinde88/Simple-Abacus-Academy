<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['user_id'];

    public function user() { return $this->belongsTo(User::class); }
    public function students() { return $this->hasMany(Student::class); }
}

