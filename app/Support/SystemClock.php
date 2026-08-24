<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class SystemClock implements Clock
{
    public function now(): DateTimeInterface
    {
        return CarbonImmutable::now();
    }
}
