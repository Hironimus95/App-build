<?php

namespace App\Jobs;

use App\Models\BlastJob;
use App\Models\BlastJobDetail;
use App\Services\GreenApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBlastToGroupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $blastJobDetailId,
        public string $chatId,
        public string $message
    ) {
    }

    public function handle(GreenApiService $greenApiService): void
    {
        $detail = BlastJobDetail::query()->findOrFail($this->blastJobDetailId);
        $detail->update(['status' => BlastJobDetail::STATUS_RUNNING]);

        try {
            $response = $greenApiService->sendGroupMessage($this->chatId, $this->message);

            $detail->update([
                'status' => BlastJobDetail::STATUS_SUCCESS,
                'response_code' => 200,
                'response_body' => json_encode($response),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $detail->update([
                'status' => BlastJobDetail::STATUS_FAILED,
                'response_code' => $exception->getCode() ?: 500,
                'response_body' => $exception->getMessage(),
                'sent_at' => now(),
            ]);

            throw $exception;
        } finally {
            $this->syncBlastJobStatus($detail->blast_job_id);
        }
    }

    private function syncBlastJobStatus(int $blastJobId): void
    {
        $summary = BlastJobDetail::query()
            ->where('blast_job_id', $blastJobId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as queued_count', [BlastJobDetail::STATUS_QUEUED])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as running_count', [BlastJobDetail::STATUS_RUNNING])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success_count', [BlastJobDetail::STATUS_SUCCESS])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_count', [BlastJobDetail::STATUS_FAILED])
            ->first();

        $total = (int) ($summary->total ?? 0);
        $queued = (int) ($summary->queued_count ?? 0);
        $running = (int) ($summary->running_count ?? 0);
        $success = (int) ($summary->success_count ?? 0);
        $failed = (int) ($summary->failed_count ?? 0);

        if ($total === 0 || $queued === $total) {
            $status = BlastJob::STATUS_QUEUED;
        } elseif ($running > 0 || ($queued > 0 && ($success + $failed) < $total)) {
            $status = BlastJob::STATUS_RUNNING;
        } elseif ($success === $total) {
            $status = BlastJob::STATUS_DONE;
        } elseif ($failed === $total) {
            $status = BlastJob::STATUS_FAILED;
        } elseif (($success + $failed) === $total && $success > 0 && $failed > 0) {
            $status = BlastJob::STATUS_PARTIAL;
        } else {
            $status = BlastJob::STATUS_RUNNING;
        }

        BlastJob::query()->whereKey($blastJobId)->update(['status' => $status]);
    }
}
