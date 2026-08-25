<?php

namespace App\Support;

use Illuminate\Support\Carbon;

interface Clock
{
    public function now(): Carbon;
}
