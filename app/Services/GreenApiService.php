<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GreenApiService
{
    public function sendGroupMessage(string $chatId, string $message): array
    {
        $response = $this->client()->post($this->sendMessageUrl(), [
            'chatId' => $chatId,
            'message' => $message,
        ])->throw();

        return Arr::wrap($response->json());
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(config('greenapi.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('greenapi.timeout', 15));
    }

    protected function sendMessageUrl(): string
    {
        return sprintf(
            '/waInstance%s/sendMessage/%s',
            config('greenapi.instance_id'),
            config('greenapi.token')
        );
    }
}
