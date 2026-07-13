<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\HmacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates internal routes (used by n8n) via HMAC-SHA256 over
 * "{X-Timestamp}.{raw body}" with the INTERNAL_API_SECRET. The comparison is
 * timing-safe and timestamps older than the configured skew are rejected to
 * prevent replay.
 */
class VerifyInternalHmac
{
    public function __construct(private readonly HmacService $hmac) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('questmap.internal_api_secret');
        $signature = (string) $request->header('X-Signature', '');
        $timestamp = (string) $request->header('X-Timestamp', '');

        if ($secret === '' || $signature === '' || $timestamp === '') {
            return $this->unauthorized('Faltan las credenciales de firma.');
        }

        if (! $this->hmac->timestampWithinSkew($timestamp, (int) config('questmap.hmac_max_skew_seconds'))) {
            return $this->unauthorized('La firma ha caducado.');
        }

        $rawBody = $request->getContent();

        if (! $this->hmac->verify($secret, $timestamp, $rawBody, $signature)) {
            return $this->unauthorized('Firma no válida.');
        }

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'message' => $message,
            'error_code' => 'invalid_signature',
        ], 401);
    }
}
