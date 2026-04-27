<?php

namespace App\Http\Controllers;

use App\Models\BlastJob;
use App\Models\BlastJobDetail;
use Illuminate\Http\JsonResponse;

class BlastMonitoringController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = BlastJob::query()
            ->latest('id')
            ->withCount([
                'details as total_groups',
                'details as success_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_SUCCESS),
                'details as failed_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_FAILED),
                'details as running_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_RUNNING),
                'details as queued_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_QUEUED),
            ])
            ->limit(20)
            ->get(['id', 'product_id', 'category', 'status', 'requested_by', 'created_at'])
            ->map(function (BlastJob $job) {
                return [
                    'id' => $job->id,
                    'product_id' => $job->product_id,
                    'category' => $job->category,
                    'status' => $job->status,
                    'requested_by' => $job->requested_by,
                    'created_at' => optional($job->created_at)?->toDateTimeString(),
                    'total_groups' => $job->total_groups,
                    'success_groups' => $job->success_groups,
                    'failed_groups' => $job->failed_groups,
                    'running_groups' => $job->running_groups,
                    'queued_groups' => $job->queued_groups,
                ];
            })
            ->values();

        return response()->json([
            'items' => $jobs,
        ]);
    }
}
