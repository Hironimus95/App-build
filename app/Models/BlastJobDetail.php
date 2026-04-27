<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlastJobDetail extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'QUEUED';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'blast_job_id',
        'wa_group_id',
        'status',
        'response_code',
        'response_body',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
