<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class SystemClock implements Clock
{
    public function now(): Carbon
    {
        return Carbon::now();
    }
}
