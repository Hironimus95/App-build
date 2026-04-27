<?php

namespace App\Http\Controllers;

use App\Services\NumberCheckerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NumberCheckerController extends Controller
{
    public function check(Request $request, NumberCheckerService $numberCheckerService): JsonResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'source' => ['nullable', 'string'],
        ]);

        $sourceMap = config('number_checker.sources', []);
        $requestedSource = $data['source'] ?? null;

        if ($requestedSource !== null && ! array_key_exists($requestedSource, $sourceMap)) {
            return response()->json([
                'message' => 'Unknown source selected.',
            ], 422);
        }

        $sources = $requestedSource === null
            ? array_values($sourceMap)
            : [
                $sourceMap[$requestedSource],
            ];

        if ($sources === []) {
            return response()->json([
                'message' => 'No number checker source is configured.',
            ], 422);
        }

        return response()->json($numberCheckerService->checkAcrossSources($data['number'], $sources));
    }
}
