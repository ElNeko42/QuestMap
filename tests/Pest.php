<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * HMAC server vars for internal routes, signed like the middleware expects:
 * signature over "{timestamp}.{rawBody}" with INTERNAL_API_SECRET.
 *
 * Returned as CGI/Symfony server vars (HTTP_* keys) so they can be passed as the
 * 6th argument to $this->call(); raw call() does NOT apply withHeaders().
 *
 * @return array<string, string>
 */
function internalHmacHeaders(string $rawBody, ?string $secret = null, ?int $timestamp = null): array
{
    $secret ??= (string) config('questmap.internal_api_secret');
    $timestamp ??= time();

    return [
        'HTTP_X_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret),
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ];
}
