<?php

namespace App\Services;

use Carbon\CarbonInterface;

final class MatterActivity
{
    public function __construct(
        public readonly CarbonInterface $at,
        public readonly string $kind,
        public readonly string $text,
        public readonly ?string $who = null,
    ) {}
}
