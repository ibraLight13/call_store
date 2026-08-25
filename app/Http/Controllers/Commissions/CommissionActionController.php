<?php

namespace App\Http\Controllers\Commissions;

use App\Domain\Commissions\Actions\ApproveCommissionProof;
use App\Domain\Commissions\Actions\CancelCommission;
use App\Domain\Commissions\Actions\CompleteCommission;
use App\Domain\Commissions\Actions\DeclineCommission;
use App\Domain\Commissions\Actions\PayCommissionCapture;
use App\Domain\Commissions\Actions\PostCommissionMessage;
use App\Domain\Commissions\Actions\QuoteCommission;
use App\Domain\Commissions\Actions\RequestCommissionRevision;
use App\Domain\Commissions\Actions\ShipCommission;
use App\Domain\Commissions\Actions\StartCommissionWork;
use App\Domain\Commissions\Actions\SubmitCommissionProof;
use App\Domain\Commissions\Models\Commission;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * One thin endpoint per lifecycle transition; every rule lives in the Actions.
 */
final class CommissionActionController extends Controller
{
    use ResolvesActor;

    public function quote(Request $request, Commission $commission): RedirectResponse
    {
        $request->validate([
            'quote' => ['required', 'numeric', 'min:50'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(QuoteCommission::class)->handle(
                seller: $this->actor($request),
                commission: $commission,
                quoteMinor: (int) round($request->float('quote') * 100),
                note: $request->filled('note') ? $request->str('note')->toString() : null,
            );
        } catch (\DomainException|\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['quote' => $e->getMessage()]);
        }

        return back()->with('status', __('commissions.quote_sent'));
    }

    public function decline(Request $request, Commission $commission): RedirectResponse
    {
        try {
            app(DeclineCommission::class)->handle(
                actor: $this->actor($request),
                commission: $commission,
                reason: $request->str('reason', '')->toString(),
            );
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    public function start(Request $request, Commission $commission): RedirectResponse
    {
        app(StartCommissionWork::class)->handle($this->actor($request), $commission);

        return back()->with('status', __('commissions.started'));
    }

    public function submitProof(Request $request, Commission $commission): RedirectResponse
    {
        $request->validate([
            'proof_image' => ['required', 'image', 'max:10240'],
            'seller_note' => ['nullable', 'string', 'max:2000'],
        ]);

        app(SubmitCommissionProof::class)->handle(
            seller: $this->actor($request),
            commission: $commission,
            image: $request->file('proof_image'),
            note: $request->filled('seller_note') ? $request->str('seller_note')->toString() : null,
        );

        return back()->with('status', __('commissions.proof_sent'));
    }

    public function revision(Request $request, Commission $commission): RedirectResponse
    {
        try {
            app(RequestCommissionRevision::class)->handle(
                buyer: $this->actor($request),
                commission: $commission,
                note: $request->str('revision_note', '')->toString(),
            );
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('commissions.revision_sent'));
    }

    public function approve(Request $request, Commission $commission): RedirectResponse
    {
        app(ApproveCommissionProof::class)->handle($this->actor($request), $commission);

        return back()->with('status', __('commissions.approved_msg'));
    }

    public function balance(Request $request, Commission $commission): RedirectResponse
    {
        // Balance is captured through the same idempotent settlement path.
        $amount = max(0, (int) $commission->quote_minor - (int) $commission->deposit_minor);

        if ($amount <= 0) {
            return back()->with('error', __('commissions.balance_not_payable'));
        }

        app(PayCommissionCapture::class)->handle(
            buyer: $this->actor($request),
            commission: $commission,
            purpose: 'balance',
            eventId: 'evt_fake_cm_'.$commission->id.'_balance_'.now()->timestamp,
            paymentRef: 'pay_fake_cm_'.$commission->id.'_balance',
        );

        return back()->with('status', __('commerce.payment_success'));
    }

    public function ship(Request $request, Commission $commission): RedirectResponse
    {
        try {
            app(ShipCommission::class)->handle(
                seller: $this->actor($request),
                commission: $commission,
                trackingNumber: $request->filled('tracking_number') ? $request->str('tracking_number')->toString() : null,
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('commerce.marked_shipped'));
    }

    public function complete(Request $request, Commission $commission): RedirectResponse
    {
        app(CompleteCommission::class)->handle($this->actor($request), $commission);

        return back()->with('status', __('commissions.completed_msg'));
    }

    public function cancel(Request $request, Commission $commission): RedirectResponse
    {
        try {
            app(CancelCommission::class)->handle($this->actor($request), $commission);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('commissions.cancelled_done'));
    }

    public function message(Request $request, Commission $commission): RedirectResponse
    {
        $request->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);

        app(PostCommissionMessage::class)->handle(
            actor: $this->actor($request),
            commission: $commission,
            body: $request->str('body')->toString(),
        );

        return back();
    }
}
