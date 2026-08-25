<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderSeller;
use App\Domain\Payments\Contracts\ConnectOnboarding;
use App\Domain\Payments\Contracts\GatewayEvent;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\User;

/**
 * Deterministic local gateway: settles synchronously so the entire commerce
 * lifecycle is demoable and testable without external credentials.
 */
final class FakeGateway implements PaymentGateway
{
    public function createConnectOnboarding(User $seller, string $returnUrl): ConnectOnboarding
    {
        $accountId = 'acct_fake_'.$seller->id;

        return new ConnectOnboarding($accountId, $returnUrl.'?fake=1');
    }

    public function refreshConnectStatus(User $seller): bool
    {
        // Fake sellers are instantly capable.
        return $seller->stripe_account_id !== null;
    }

    public function createCheckoutSession(Order $order): string
    {
        return route('checkout.fake_pay', ['order' => $order]);
    }

    /**
     * The fake webhook accepts anything signed by our own confirmation step.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function parseWebhook(array $payload, array $headers): ?GatewayEvent
    {
        $id = $payload['event_id'] ?? null;
        $ref = $payload['checkout_ref'] ?? null;
        $type = $payload['type'] ?? null;

        if (! is_string($id) || ! is_string($type)) {
            return null;
        }

        if (isset($headers['X-Fake-Signature']) === false) {
            return null;
        }

        return new GatewayEvent(
            id: $id,
            type: $type,
            data: ['checkout_ref' => is_string($ref) ? $ref : null],
        );
    }

    public function createTransfer(OrderSeller $group): string
    {
        return 'tr_fake_'.$group->order_id.'_'.$group->seller_id;
    }

    public function reverseTransfer(OrderSeller $group): void
    {
        // Instant, deterministic reversal.
    }

    public function createCommissionCheckout(Commission $commission, string $purpose, int $amountMinor): string
    {
        return route('commissions.fake_pay', ['commission' => $commission, 'purpose' => $purpose]);
    }

    public function createCommissionTransfer(Commission $commission): string
    {
        return 'tr_fake_cm_'.$commission->id.'_'.$commission->seller_id;
    }

    public function reverseCommissionTransfer(Commission $commission): void
    {
        // Instant, deterministic reversal.
    }
}
