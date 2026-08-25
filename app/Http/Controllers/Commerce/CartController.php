<?php

namespace App\Http\Controllers\Commerce;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Orders\Actions\AddToCart;
use App\Domain\Orders\Actions\CartQuery;
use App\Domain\Orders\Actions\RemoveFromCart;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CartController extends Controller
{
    use ResolvesActor;

    public function index(Request $request): View
    {
        return view('pages.cart', [
            'cart' => app(CartQuery::class)->for($this->actor($request)),
        ]);
    }

    public function add(Request $request, Piece $piece): RedirectResponse
    {
        try {
            $added = app(AddToCart::class)->handle($this->actor($request), $piece);
        } catch (\InvalidArgumentException|\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $added) {
            return back()->with('status', __('commerce.already_in_cart'));
        }

        return redirect()
            ->route('cart.index')
            ->with('status', __('commerce.added_to_cart'));
    }

    public function remove(Request $request, int $piece): RedirectResponse
    {
        app(RemoveFromCart::class)->handle($this->actor($request), $piece);

        return back()->with('status', __('commerce.removed_from_cart'));
    }
}
