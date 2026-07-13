<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\RouteResource;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RouteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Route::query();

        if ($request->filled('theme')) {
            $query->where('theme', $request->string('theme'));
        }

        // Scope to the user's tenant when known.
        if ($request->user()?->tenant_id !== null) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        return RouteResource::collection($query->orderBy('title')->get());
    }

    public function show(Route $route): RouteResource
    {
        return (new RouteResource($route))->withQuests();
    }
}
