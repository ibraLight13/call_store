<?php

namespace App\Http\Controllers\Commissions;

use App\Domain\Commissions\Actions\PayCommissionCapture;
use App\Domain\Commissions\Models\Commission;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Local-only settlement for commission captures under the FakeGateway.
 */
final class CommissionFakePayController extends Controller
{
    use ResolvesActor;

    public function pay(
        Request $request,
        Commission $commission,
        string $purpose,
    ): RedirectResponse {
        $actor = $this->actor($request);

        if ((int) $commission->buyer_id !== (int) $actor->id) {
            abort(403);
        }

        if (app()->environment('production') || config('commerce.gateway') !== 'fake') {
            abort(404);
        }

        if (! in_array($purpose, ['deposit', 'balance'], true)) {
            abort(404);
        }

        try {
            $stamp = $commission->updated_at;

            app(PayCommissionCapture::class)->handle(
                buyer: $this->actor($request),
                commission: $commission,
                purpose: $purpose,
                eventId: 'evt_fake_cm_'.$commission->id.'_'.$purpose.'_'.($stamp !== null ? $stamp->timestamp : time()),
                paymentRef: 'pay_fake_cm_'.$commission->id.'_'.$purpose,
            );
        } catch (\DomainException $e) {
            throw ValidationException::withMessages(['purpose' => $e->getMessage()]);
        }

        return redirect()
            ->route('commissions.show', $commission)
            ->with('status', __('commerce.payment_success'));
    }
}
