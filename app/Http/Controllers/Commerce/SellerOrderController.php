<?php

namespace App\Http\Controllers\Commerce;

use App\Domain\Orders\Actions\ShipSellerGroup;
use App\Domain\Orders\Models\OrderSeller;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SellerOrderController extends Controller
{
    use ResolvesActor;

    public function index(Request $request): View
    {
        $actor = $this->actor($request);

        $groups = OrderSeller::query()
            ->where('seller_id', $actor->id)
            ->whereHas('order', fn ($q) => $q->whereIn('state', ['paid', 'completed']))
            ->with(['order:id,state,buyer_id,placed_at,paid_at,address', 'order.buyer:id,name'])
            ->latest('id')
            ->paginate(15);

        return view('pages.seller-orders', ['groups' => $groups]);
    }

    public function ship(Request $request, OrderSeller $group): RedirectResponse
    {
        $tracking = $request->filled('tracking_number')
            ? $request->str('tracking_number')->toString()
            : null;

        app(ShipSellerGroup::class)->handle(
            seller: $this->actor($request),
            group: $group,
            trackingNumber: $tracking,
        );

        return back()->with('status', __('commerce.marked_shipped'));
    }
}
