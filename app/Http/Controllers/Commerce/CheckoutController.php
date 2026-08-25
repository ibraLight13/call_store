<?php

namespace App\Http\Controllers\Commerce;

use App\Domain\Orders\Actions\CartQuery;
use App\Domain\Orders\Actions\StartCheckout;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CheckoutController extends Controller
{
    use ResolvesActor;

    public function create(Request $request): View|RedirectResponse
    {
        $actor = $this->actor($request);
        $cart = app(CartQuery::class)->for($actor);

        if ($cart['groups']->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('commerce.cart_empty'));
        }

        return view('pages.checkout', [
            'cart' => $cart,
            'addresses' => $actor->addresses()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);

        $request->validate([
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'recipient_name' => ['required_without:address_id', 'string', 'max:255'],
            'phone' => ['required_without:address_id', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'size:2'],
            'city' => ['required_without:address_id', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:150'],
            'street' => ['required_without:address_id', 'string', 'max:200'],
            'building' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'save_address' => ['nullable', 'boolean'],
        ]);

        $address = $this->resolveAddress($request);

        if ($address === null) {
            return back()->with('error', __('commerce.address_not_found'));
        }

        try {
            $order = app(StartCheckout::class)->handle($actor, $address);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->boolean('save_address') && ! $request->filled('address_id')) {
            $actor->addresses()->create($address);
        }

        $checkoutUrl = app(PaymentGateway::class)
            ->createCheckoutSession($order);

        return redirect()->away($checkoutUrl);
    }

    /**
     * Snapshot from a saved address or the posted form fields.
     *
     * @return array{recipient_name: string, phone: string, country: string, city: string, district: string|null, street: string, building: string|null, postal_code: string|null}|null
     */
    private function resolveAddress(Request $request): ?array
    {
        if ($request->filled('address_id')) {
            $saved = $this->actor($request)
                ->addresses()
                ->whereKey((int) $request->integer('address_id'))
                ->first();

            if ($saved === null) {
                return null;
            }

            return [
                'recipient_name' => $saved->recipient_name,
                'phone' => $saved->phone,
                'country' => (string) $saved->country,
                'city' => $saved->city,
                'district' => $saved->district,
                'street' => $saved->street,
                'building' => $saved->building,
                'postal_code' => $saved->postal_code,
            ];
        }

        return [
            'recipient_name' => $request->str('recipient_name')->toString(),
            'phone' => $request->str('phone')->toString(),
            'country' => $request->filled('country') ? $request->str('country')->toString() : 'SA',
            'city' => $request->str('city')->toString(),
            'district' => $request->filled('district') ? $request->str('district')->toString() : null,
            'street' => $request->str('street')->toString(),
            'building' => $request->filled('building') ? $request->str('building')->toString() : null,
            'postal_code' => $request->filled('postal_code') ? $request->str('postal_code')->toString() : null,
        ];
    }
}
