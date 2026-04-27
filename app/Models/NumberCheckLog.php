<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberCheckLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'source_db',
        'raw_number',
        'normalized_number',
        'is_valid',
        'exists_in_source',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'is_valid' => 'boolean',
        'exists_in_source' => 'boolean',
    ];
}
