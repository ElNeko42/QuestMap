<?php

declare(strict_types=1);

namespace App\Services;

/**
 * HMAC-SHA256 signing/verification for the internal API surface shared with n8n.
 *
 * The signed message is "{timestamp}.{rawBody}" so the timestamp is covered by
 * the signature and cannot be tampered with independently (replay protection is
 * enforced by the caller checking the timestamp skew).
 */
class HmacService
{
    /**
     * Build the canonical signing string.
     */
    public function payload(string $timestamp, string $rawBody): string
    {
        return $timestamp.'.'.$rawBody;
    }

    public function sign(string $secret, string $timestamp, string $rawBody): string
    {
        return hash_hmac('sha256', $this->payload($timestamp, $rawBody), $secret);
    }

    /**
     * Timing-safe comparison of a provided signature against the expected one.
     */
    public function verify(string $secret, string $timestamp, string $rawBody, string $signature): bool
    {
        $expected = $this->sign($secret, $timestamp, $rawBody);

        return hash_equals($expected, $signature);
    }

    /**
     * Whether the timestamp is within the allowed clock skew (seconds).
     */
    public function timestampWithinSkew(string|int $timestamp, int $maxSkewSeconds): bool
    {
        if (! is_numeric($timestamp)) {
            return false;
        }

        return abs(time() - (int) $timestamp) <= $maxSkewSeconds;
    }
}
