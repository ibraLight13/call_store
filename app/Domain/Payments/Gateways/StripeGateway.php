<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderSeller;
use App\Domain\Payments\Contracts\ConnectOnboarding;
use App\Domain\Payments\Contracts\GatewayEvent;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\User;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Real Stripe implementation. Dormant until STRIPE_SECRET is configured â€”
 * the container binds FakeGateway until then. Uses "separate charges and
 * transfers": the platform owns the charge, transfers fan out per seller.
 */
final class StripeGateway implements PaymentGateway
{
    private StripeClient $client;

    public function __construct(private readonly string $secretKey, private readonly string $webhookSecret)
    {
        $this->client = new StripeClient($secretKey);
    }

    public function createConnectOnboarding(User $seller, string $returnUrl): ConnectOnboarding
    {
        $accountId = $seller->stripe_account_id;

        if ($accountId === null) {
            $account = $this->client->accounts->create([
                'type' => 'express',
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => ['name' => $seller->name],
            ]);

            $accountId = (string) $account->id;
            $seller->forceFill(['stripe_account_id' => $accountId])->save();
        }

        $link = $this->client->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => $returnUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return new ConnectOnboarding($accountId, (string) $link->url);
    }

    public function refreshConnectStatus(User $seller): bool
    {
        if ($seller->stripe_account_id === null) {
            return false;
        }

        $account = $this->client->accounts->retrieve($seller->stripe_account_id);
        $enabled = (bool) $account->charges_enabled;

        $seller->forceFill(['stripe_charges_enabled' => $enabled])->save();

        return $enabled;
    }

    public function createCheckoutSession(Order $order): string
    {
        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => $item->price_minor,
                    'product_data' => ['name' => mb_substr($item->text, 0, 120)],
                ],
            ];
        }

        $lineItems[] = [
            'quantity' => 1,
            'price_data' => [
                'currency' => strtolower($order->currency),
                'unit_amount' => $order->shipping_minor,
                'product_data' => ['name' => __('commerce.shipping')],
            ],
        ];

        $session = $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => (string) $order->id,
            'metadata' => ['order_id' => (string) $order->id],
            'success_url' => route('orders.show', $order).'?paid=1',
            'cancel_url' => route('cart.index'),
            'customer_email' => $order->buyer->email,
            'line_items' => $lineItems,
        ]);

        return (string) $session->url;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function parseWebhook(array $payload, array $headers): ?GatewayEvent
    {
        $signature = $headers['Stripe-Signature'] ?? $headers['stripe_signature'] ?? null;
        $raw = json_encode($payload);

        if (! is_string($signature) || ! is_string($raw)) {
            return null;
        }

        try {
            $event = Webhook::constructEvent($raw, $signature, $this->webhookSecret);
        } catch (\Throwable) {
            return null;
        }

        $checkoutRef = null;

        if ($event->type === 'checkout.session.completed') {
            $object = $event->data->object ?? null;
            $ref = is_object($object) ? ($object->id ?? null) : null;
            $checkoutRef = is_string($ref) ? $ref : null;
        }

        return new GatewayEvent(
            id: (string) $event->id,
            type: (string) $event->type,
            data: ['checkout_ref' => $checkoutRef],
        );
    }

    public function createTransfer(OrderSeller $group): string
    {
        $order = $group->order;
        $accountId = $group->seller->stripe_account_id;

        if ($accountId === null) {
            throw new \RuntimeException(__('commerce.seller_not_connected'));
        }

        $transfer = $this->client->transfers->create([
            'amount' => $group->subtotal_minor + $group->shipping_minor,
            'currency' => strtolower($order->currency),
            'destination' => $accountId,
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        return (string) $transfer->id;
    }

    public function reverseTransfer(OrderSeller $group): void
    {
        if ($group->transfer_ref === null || str_starts_with($group->transfer_ref, 'tr_fake_')) {
            return;
        }

        $this->client->transfers->createReversal($group->transfer_ref);
    }
}
