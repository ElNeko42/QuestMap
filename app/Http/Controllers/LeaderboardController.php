<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaderboardController extends Controller
{
    public function __construct(private readonly LeaderboardService $leaderboard) {}

    public function index(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'period' => ['sometimes', 'in:week,month,all'],
        ])->validate();

        $period = $data['period'] ?? 'week';
        $user = $request->user();

        if ($user->tenant_id === null) {
            return response()->json([
                'message' => 'Aún no perteneces a ninguna ciudad. Comparte tu ubicación primero.',
                'error_code' => 'no_tenant',
                'period' => $period,
                'data' => [],
            ], 200);
        }

        return response()->json([
            'period' => $period,
            'tenant_id' => $user->tenant_id,
            'data' => $this->leaderboard->top($user->tenant_id, $period),
        ]);
    }
}
