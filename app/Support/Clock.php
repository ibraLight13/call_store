<?php

namespace App\Support;

use DateTimeInterface;

interface Clock
{
    public function now(): DateTimeInterface;
}
