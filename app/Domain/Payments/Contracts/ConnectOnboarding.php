<?php

namespace App\Domain\Payments\Contracts;

final class ConnectOnboarding
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $redirectUrl,
    ) {}
}
