<?php

namespace App\Http\Controllers;

use App\Models\BlastJob;
use App\Models\BlastJobDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlastMonitoringController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:QUEUED,RUNNING,DONE,FAILED,PARTIAL'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);

        $query = BlastJob::query()
            ->latest('id')
            ->withCount([
                'details as total_groups',
                'details as success_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_SUCCESS),
                'details as failed_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_FAILED),
                'details as running_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_RUNNING),
                'details as queued_groups' => fn ($query) => $query->where('status', BlastJobDetail::STATUS_QUEUED),
            ]);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $jobs = $query
            ->limit($limit)
            ->get(['id', 'product_id', 'category', 'status', 'requested_by', 'created_at'])
            ->map(function (BlastJob $job) {
                $total = (int) $job->total_groups;
                $success = (int) $job->success_groups;

                return [
                    'id' => $job->id,
                    'product_id' => $job->product_id,
                    'category' => $job->category,
                    'status' => $job->status,
                    'requested_by' => $job->requested_by,
                    'created_at' => optional($job->created_at)?->toDateTimeString(),
                    'total_groups' => $total,
                    'success_groups' => $success,
                    'failed_groups' => (int) $job->failed_groups,
                    'running_groups' => (int) $job->running_groups,
                    'queued_groups' => (int) $job->queued_groups,
                    'progress_percent' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
                ];
            })
            ->values();

        return response()->json([
            'filters' => [
                'status' => $validated['status'] ?? null,
                'limit' => $limit,
            ],
            'items' => $jobs,
        ]);
    }
}
