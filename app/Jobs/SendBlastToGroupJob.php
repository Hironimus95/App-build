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
        $total = BlastJobDetail::query()->where('blast_job_id', $blastJobId)->count();
        $queued = BlastJobDetail::query()->where('blast_job_id', $blastJobId)->where('status', BlastJobDetail::STATUS_QUEUED)->count();
        $running = BlastJobDetail::query()->where('blast_job_id', $blastJobId)->where('status', BlastJobDetail::STATUS_RUNNING)->count();
        $success = BlastJobDetail::query()->where('blast_job_id', $blastJobId)->where('status', BlastJobDetail::STATUS_SUCCESS)->count();
        $failed = BlastJobDetail::query()->where('blast_job_id', $blastJobId)->where('status', BlastJobDetail::STATUS_FAILED)->count();

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
