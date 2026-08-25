<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderSeller;
use App\Models\User;

interface PaymentGateway
{
    public function createConnectOnboarding(User $seller, string $returnUrl): ConnectOnboarding;

    public function refreshConnectStatus(User $seller): bool;

    /**
     * Create a hosted checkout session for a placed order.
     * Returns the URL the buyer must be redirected to.
     */
    public function createCheckoutSession(Order $order): string;

    /**
     * Verify + decode an inbound webhook payload. Returns null when invalid.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function parseWebhook(array $payload, array $headers): ?GatewayEvent;

    /** Move this seller group's share to the seller's connected account. */
    public function createTransfer(OrderSeller $group): string;

    /** Reverse a previously created transfer (refund path). */
    public function reverseTransfer(OrderSeller $group): void;
}
