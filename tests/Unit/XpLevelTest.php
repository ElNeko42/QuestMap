<?php

declare(strict_types=1);

use App\Services\XpService;

beforeEach(function () {
    $this->xp = new XpService();
});

it('computes level from xp with the sqrt curve', function (int $xp, int $expected) {
    expect($this->xp->levelForXp($xp))->toBe($expected);
})->with([
    'zero' => [0, 1],
    'just below L2' => [99, 1],
    'exactly L2' => [100, 2],
    'mid L2' => [399, 2],
    'L3 boundary' => [400, 3],
    'L4 boundary' => [900, 4],
    'L5 boundary' => [1600, 5],
    'high' => [10000, 11],
]);

it('never returns a level below 1', function () {
    expect($this->xp->levelForXp(-50))->toBe(1);
});
