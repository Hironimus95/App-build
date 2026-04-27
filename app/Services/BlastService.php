<?php

namespace App\Services;

use App\Jobs\SendBlastToGroupJob;
use App\Models\BlastJob;
use App\Models\BlastJobDetail;
use App\Models\BlastTemplate;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BlastService
{
    public function createAndDispatchBlast(int $productId, string $category, array $payload, string $requestedBy): BlastJob
    {
        return DB::transaction(function () use ($productId, $category, $payload, $requestedBy) {
            $product = Product::query()->with(['waGroups' => fn ($q) => $q->where('is_active', true)])->findOrFail($productId);
            $template = BlastTemplate::query()->where('category', $category)->where('is_active', true)->firstOrFail();

            $blastJob = BlastJob::query()->create([
                'product_id' => $product->id,
                'category' => $category,
                'payload_json' => $payload,
                'status' => BlastJob::STATUS_QUEUED,
                'requested_by' => $requestedBy,
            ]);

            foreach ($product->waGroups as $group) {
                $message = $this->renderTemplate($template->body, Arr::add($payload, 'product_name', $product->name));

                $detail = BlastJobDetail::query()->create([
                    'blast_job_id' => $blastJob->id,
                    'wa_group_id' => $group->id,
                    'status' => BlastJobDetail::STATUS_QUEUED,
                ]);

                SendBlastToGroupJob::dispatch($detail->id, $group->chat_id, $message);
            }

            return $blastJob;
        });
    }

    public function renderTemplate(string $template, array $data): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function (array $matches) use ($data) {
            return (string) ($data[$matches[1]] ?? $matches[0]);
        }, $template) ?? $template;
    }
}
