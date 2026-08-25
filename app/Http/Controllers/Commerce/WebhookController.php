<?php

namespace App\Http\Controllers\Commerce;

use App\Domain\Payments\Actions\MarkOrderPaid;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly MarkOrderPaid $markPaid,
    ) {}

    public function stripe(Request $request): JsonResponse
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        $event = $this->gateway->parseWebhook(
            payload: (array) $request->json()->all(),
            headers: $headers,
        );

        $checkoutRef = $event?->checkoutRef();

        if ($event === null || $event->type !== 'checkout.session.completed' || $checkoutRef === null) {
            return response()->json(['ignored' => true]);
        }

        $this->markPaid->handle(
            gateway: 'stripe',
            eventId: $event->id,
            checkoutRef: $checkoutRef,
            paymentRef: $checkoutRef,
        );

        return response()->json(['received' => true]);
    }
}
