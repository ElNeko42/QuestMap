<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FriendRequestRequest;
use App\Http\Resources\FriendResource;
use App\Models\User;
use App\Services\FriendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FriendController extends Controller
{
    public function __construct(private readonly FriendService $friends) {}

    /**
     * Accepted friends, ordered by XP (for comparison), with the caller's own
     * rank position implied client-side.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return FriendResource::collection($this->friends->friends($request->user()));
    }

    /**
     * Incoming pending friend requests.
     */
    public function requests(Request $request): JsonResponse
    {
        $incoming = $this->friends->incomingRequests($request->user());

        return response()->json([
            'data' => $incoming->map(fn ($f) => [
                'id' => $f->id,
                'from' => new FriendResource($f->requester),
                'created_at' => $f->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Send a friend request by email (auto-accepts a reciprocal request).
     */
    public function store(FriendRequestRequest $request): JsonResponse
    {
        $friendship = $this->friends->sendRequest(
            $request->user(),
            $request->validated('email'),
        );

        $accepted = $friendship->status->value === 'accepted';

        return response()->json([
            'message' => $accepted
                ? '¡Ahora sois amigos!'
                : 'Solicitud enviada.',
            'status' => $friendship->status->value,
        ], 201);
    }

    /**
     * Accept a request received from {user}.
     */
    public function accept(Request $request, User $user): JsonResponse
    {
        $this->friends->accept($request->user(), $user->id);

        return response()->json(['message' => '¡Ahora sois amigos!']);
    }

    /**
     * Remove a friend, or decline/cancel a pending request with {user}.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->friends->remove($request->user(), $user->id);

        return response()->json(['message' => 'Hecho.']);
    }
}
