<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\Internal\PhotoController;
use App\Http\Controllers\Internal\QuestBatchController;
use App\Http\Controllers\Internal\ValidationCallbackController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public auth
|--------------------------------------------------------------------------
*/
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/me', [MeController::class, 'show']);
    Route::patch('/me', [MeController::class, 'updateProfile']);
    Route::put('/me/password', [MeController::class, 'updatePassword']);
    Route::post('/me/location', [MeController::class, 'updateLocation']);
    Route::get('/me/completions', [MeController::class, 'completions']);
    Route::post('/me/target', [MeController::class, 'setTarget']);
    Route::delete('/me/target', [MeController::class, 'clearTarget']);

    // Friends
    Route::get('/friends', [FriendController::class, 'index']);
    Route::get('/friends/requests', [FriendController::class, 'requests']);
    Route::post('/friends/request', [FriendController::class, 'store']);
    Route::post('/friends/{user}/accept', [FriendController::class, 'accept']);
    Route::delete('/friends/{user}', [FriendController::class, 'destroy']);

    Route::get('/quests/nearby', [QuestController::class, 'nearby']);
    Route::get('/quests/{quest}', [QuestController::class, 'show']);
    Route::post('/quests/{quest}/checkin', [QuestController::class, 'checkin']);
    Route::post('/quests/{quest}/submit', [QuestController::class, 'submit'])
        ->middleware('throttle:submits');

    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    Route::get('/routes', [RouteController::class, 'index']);
    Route::get('/routes/{route}', [RouteController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Internal (n8n) — HMAC authenticated
|--------------------------------------------------------------------------
*/
Route::prefix('internal')->middleware('internal.hmac')->group(function () {
    Route::post('/quests/batch', [QuestBatchController::class, 'store']);
    Route::post('/completions/{completion}/validation-callback', [ValidationCallbackController::class, 'store']);
});

// Signed (not HMAC): temporary photo URL handed to n8n by the validation job.
Route::get('/internal/photos/{completion}', [PhotoController::class, 'show'])
    ->middleware('signed')
    ->name('internal.photo');
