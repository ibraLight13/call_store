<?php

namespace App\Http\Controllers\Commerce;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Actions\MarkOrderPaid;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Local-only settlement path used by the FakeGateway. Refuses to run when a
 * real gateway is configured or in production.
 */
final class FakePayController extends Controller
{
    use ResolvesActor;

    public function pay(
        Request $request,
        Order $order,
        MarkOrderPaid $markPaid,
    ): RedirectResponse {
        $actor = $this->actor($request);

        if ((int) $order->buyer_id !== (int) $actor->id) {
            abort(403);
        }

        if (app()->environment('production') || config('commerce.gateway') !== 'fake') {
            abort(404);
        }

        // Route through the idempotent settlement so both paths share one codebase.
        $markPaid->handle(
            gateway: 'fake',
            eventId: 'evt_fake_'.$order->id.'_'.$order->checkout_ref,
            checkoutRef: (string) $order->checkout_ref,
            paymentRef: 'pay_fake_'.$order->id,
        );

        return redirect()
            ->route('orders.show', $order)
            ->with('status', __('commerce.payment_success'));
    }
}
