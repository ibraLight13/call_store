<?php

namespace App\Http\Controllers\Commerce;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Actions\RefundOrder;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class RefundController extends Controller
{
    use ResolvesActor;

    public function store(Request $request, Order $order): RedirectResponse
    {
        if (! Gate::forUser($this->actor($request))->allows('refund', $order)) {
            abort(403);
        }

        app(RefundOrder::class)->handle($this->actor($request), $order);

        return back()->with('status', __('commerce.refunded_note'));
    }
}
