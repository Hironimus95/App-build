<?php

namespace App\Jobs;

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
        }
    }
}
