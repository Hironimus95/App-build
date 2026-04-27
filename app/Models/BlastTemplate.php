<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlastTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'title',
        'body',
        'is_active',
    ];
}
