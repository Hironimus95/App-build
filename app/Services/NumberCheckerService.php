<?php

namespace App\Services;

use App\Models\NumberCheckLog;
use Illuminate\Support\Facades\DB;

class NumberCheckerService
{
    public function normalize(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return '62' . $digits;
        }

        return $digits;
    }

    public function checkAcrossSources(string $number, array $sources): array
    {
        $normalized = $this->normalize($number);
        $result = [];

        foreach ($sources as $source) {
            $exists = DB::connection($source['connection'])
                ->table($source['table'])
                ->where($source['column'], $normalized)
                ->exists();

            $result[] = [
                'source_db' => $source['connection'],
                'exists' => $exists,
            ];

            NumberCheckLog::query()->create([
                'source_db' => $source['connection'],
                'raw_number' => $number,
                'normalized_number' => $normalized,
                'is_valid' => strlen($normalized) >= 10,
                'exists_in_source' => $exists,
                'checked_at' => now(),
            ]);
        }

        return [
            'raw_number' => $number,
            'normalized_number' => $normalized,
            'sources' => $result,
            'duplicate_count' => collect($result)->where('exists', true)->count(),
        ];
    }
}
