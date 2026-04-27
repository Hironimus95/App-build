<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlastJob extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'QUEUED';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE = 'DONE';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_PARTIAL = 'PARTIAL';

    protected $fillable = [
        'product_id',
        'category',
        'payload_json',
        'status',
        'requested_by',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];
}
