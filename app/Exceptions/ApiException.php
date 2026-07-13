<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base class for domain errors surfaced to the API as JSON. Messages may be in
 * Spanish (user-facing); `error_code` is a stable machine string.
 */
class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'error',
        public readonly int $status = 422,
        public readonly array $meta = [],
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'meta' => $this->meta ?: null,
        ], static fn ($v) => $v !== null), $this->status);
    }
}
