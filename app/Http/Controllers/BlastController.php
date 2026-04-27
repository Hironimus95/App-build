<?php

namespace App\Http\Controllers;

use App\Services\BlastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlastController extends Controller
{
    public function send(Request $request, BlastService $blastService): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'category' => ['required', 'in:INCIDENT,MAINTENANCE'],
            'payload' => ['required', 'array'],
        ]);

        $blast = $blastService->createAndDispatchBlast(
            $data['product_id'],
            $data['category'],
            $data['payload'],
            (string) ($request->user()->email ?? 'system')
        );

        return response()->json([
            'message' => 'Blast queued',
            'blast_job_id' => $blast->id,
            'status' => $blast->status,
        ], 202);
    }
}
