<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\ValidationCallbackRequest;
use App\Http\Resources\CompletionResource;
use App\Models\QuestCompletion;
use App\Services\CompletionService;
use Illuminate\Http\JsonResponse;

class ValidationCallbackController extends Controller
{
    public function __construct(private readonly CompletionService $completionService) {}

    /**
     * Receives the AI verdict from n8n. Idempotent: if the completion is no
     * longer pending, responds 200 without re-processing.
     */
    public function store(ValidationCallbackRequest $request, QuestCompletion $completion): JsonResponse
    {
        $completion = $this->completionService->applyValidation(
            $completion,
            (bool) $request->validated('valid'),
            (float) $request->validated('confidence'),
            $request->validated('reason'),
        );

        return response()->json([
            'completion' => new CompletionResource($completion),
        ]);
    }
}
