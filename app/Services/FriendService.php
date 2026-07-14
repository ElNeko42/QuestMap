<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FriendshipStatus;
use App\Exceptions\ApiException;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class FriendService
{
    /**
     * Send a friend request from $user to the user with $email.
     * Auto-accepts if the other person already requested us.
     */
    public function sendRequest(User $user, string $email): Friendship
    {
        $other = User::where('email', $email)->first();

        if ($other === null) {
            throw new ApiException('No existe ningún usuario con ese email.', 'user_not_found', 404);
        }

        if ($other->id === $user->id) {
            throw new ApiException('No puedes añadirte a ti mismo.', 'self_friend', 422);
        }

        // Already related in some direction?
        $existing = $this->between($user->id, $other->id);

        if ($existing !== null) {
            if ($existing->status === FriendshipStatus::Accepted) {
                throw new ApiException('Ya sois amigos.', 'already_friends', 409);
            }

            // The other person already sent us a request -> accept it.
            if ($existing->friend_id === $user->id) {
                $existing->update(['status' => FriendshipStatus::Accepted]);

                return $existing;
            }

            throw new ApiException('Ya has enviado una solicitud a este usuario.', 'request_pending', 409);
        }

        return Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $other->id,
            'status' => FriendshipStatus::Pending,
        ]);
    }

    /**
     * Accept a pending request that $user received from $requesterId.
     */
    public function accept(User $user, int $requesterId): Friendship
    {
        $friendship = Friendship::query()
            ->where('user_id', $requesterId)
            ->where('friend_id', $user->id)
            ->where('status', FriendshipStatus::Pending->value)
            ->first();

        if ($friendship === null) {
            throw new ApiException('No hay ninguna solicitud pendiente de este usuario.', 'no_request', 404);
        }

        $friendship->update(['status' => FriendshipStatus::Accepted]);

        return $friendship;
    }

    /**
     * Decline a received request, or remove an existing friendship / sent
     * request between the two users (either direction).
     */
    public function remove(User $user, int $otherId): void
    {
        Friendship::query()
            ->where(function ($q) use ($user, $otherId) {
                $q->where('user_id', $user->id)->where('friend_id', $otherId);
            })
            ->orWhere(function ($q) use ($user, $otherId) {
                $q->where('user_id', $otherId)->where('friend_id', $user->id);
            })
            ->delete();
    }

    /**
     * Accepted friends of $user (either direction), ordered by XP desc so the
     * client can show a comparison.
     *
     * @return Collection<int, User>
     */
    public function friends(User $user): Collection
    {
        $ids = Friendship::query()
            ->where('status', FriendshipStatus::Accepted->value)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('friend_id', $user->id);
            })
            ->get()
            ->map(fn (Friendship $f) => $f->user_id === $user->id ? $f->friend_id : $f->user_id)
            ->unique()
            ->values();

        return User::whereIn('id', $ids)
            ->orderByDesc('xp')
            ->orderBy('name')
            ->get();
    }

    /**
     * Pending requests $user has received (incoming), with the requester loaded.
     *
     * @return Collection<int, Friendship>
     */
    public function incomingRequests(User $user): Collection
    {
        return Friendship::query()
            ->with('requester')
            ->where('friend_id', $user->id)
            ->where('status', FriendshipStatus::Pending->value)
            ->latest()
            ->get();
    }

    private function between(int $a, int $b): ?Friendship
    {
        return Friendship::query()
            ->where(function ($q) use ($a, $b) {
                $q->where('user_id', $a)->where('friend_id', $b);
            })
            ->orWhere(function ($q) use ($a, $b) {
                $q->where('user_id', $b)->where('friend_id', $a);
            })
            ->first();
    }
}
