<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CheckinRequest;
use App\Http\Requests\SubmitRequest;
use App\Http\Resources\CompletionResource;
use App\Http\Resources\QuestResource;
use App\Models\Quest;
use App\Services\CompletionService;
use App\Services\QuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class QuestController extends Controller
{
    public function __construct(
        private readonly QuestService $questService,
        private readonly CompletionService $completionService,
    ) {}

    /**
     * GET /quests/nearby?lat&lng&radius — active quests ordered by distance.
     */
    public function nearby(Request $request): AnonymousResourceCollection
    {
        $data = Validator::make($request->all(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'integer', 'min:1'],
            'category' => ['sometimes', 'string', 'max:64'],
        ])->validate();

        $radius = (int) ($data['radius'] ?? config('questmap.default_nearby_radius_m'));
        $radius = min($radius, (int) config('questmap.max_nearby_radius_m'));

        $point = new \App\Support\GeoPoint((float) $data['lat'], (float) $data['lng']);

        $quests = $this->questService->nearby(
            $point,
            $radius,
            $request->user()?->tenant_id,
            $data['category'] ?? null,
        );

        return QuestResource::collection($quests);
    }

    public function show(Quest $quest): QuestResource
    {
        return new QuestResource($quest);
    }

    public function checkin(CheckinRequest $request, Quest $quest): JsonResponse
    {
        $completion = $this->completionService->checkin(
            $request->user(),
            $quest,
            $request->geoPoint(),
            now(),
        );

        return response()->json([
            'message' => '¡Check-in completado!',
            'completion' => new CompletionResource($completion->load('quest')),
        ], 201);
    }

    public function submit(SubmitRequest $request, Quest $quest): JsonResponse
    {
        $completion = $this->completionService->submitPhoto(
            $request->user(),
            $quest,
            $request->geoPoint(),
            $request->file('photo'),
            now(),
        );

        return response()->json([
            'message' => 'Foto recibida. La validaremos en unos momentos.',
            'completion' => new CompletionResource($completion->load('quest')),
        ], 202);
    }
}
