<?php

namespace App\Domain\Payments\Contracts;

/**
 * Normalized gateway event consumed by webhook handling.
 */
final class GatewayEvent
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        /** @var array<string, mixed> */
        public readonly array $data,
    ) {}

    public function checkoutRef(): ?string
    {
        $ref = $this->data['checkout_ref'] ?? null;

        return is_string($ref) ? $ref : null;
    }
}
