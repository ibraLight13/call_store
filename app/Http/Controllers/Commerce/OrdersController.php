<?php

namespace App\Http\Controllers\Commerce;

use App\Domain\Orders\Models\Order;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class OrdersController extends Controller
{
    use ResolvesActor;

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->where('buyer_id', $this->actor($request)->id)
            ->with('items')
            ->latest('id')
            ->paginate(15);

        return view('pages.orders', ['orders' => $orders]);
    }

    public function show(Request $request, Order $order): View
    {
        Gate::authorize('view', $order);

        return view('pages.order', [
            'order' => $order->load(['items.piece:id,seller_id', 'sellers.seller:id,name,username']),
        ]);
    }
}
