<?php

namespace App\Http\Controllers\Commissions;

use App\Domain\Catalog\Support\TaxonomyRepository;
use App\Domain\Commissions\Actions\RequestCommission;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CommissionRequestController extends Controller
{
    use ResolvesActor;

    public function create(Request $request, User $seller): View
    {
        if (! $seller->hasRole('seller')) {
            abort(404);
        }

        return view('pages.commission-request', [
            'seller' => $seller,
            'taxonomy' => app(TaxonomyRepository::class)->all(),
        ]);
    }

    public function store(Request $request, User $seller): RedirectResponse
    {
        if (! $seller->hasRole('seller')) {
            abort(404);
        }

        $actor = $this->actor($request);

        $data = $request->validate([
            'text' => ['required', 'string', 'min:2', 'max:500'],
            'text_language' => ['required', 'in:ar,en'],
            'script_id' => ['nullable', 'integer', 'exists:scripts,id'],
            'medium_id' => ['nullable', 'integer', 'exists:mediums,id'],
            'width_cm' => ['nullable', 'integer', 'min:5', 'max:500'],
            'height_cm' => ['nullable', 'integer', 'min:5', 'max:500'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $commission = app(RequestCommission::class)->handle($actor, $seller, [
            'text' => $request->str('text')->toString(),
            'text_language' => $request->str('text_language', 'ar')->toString() === 'en' ? 'en' : 'ar',
            'script_id' => $request->integer('script_id') ?: null,
            'medium_id' => $request->integer('medium_id') ?: null,
            'width_cm' => $request->integer('width_cm') ?: null,
            'height_cm' => $request->integer('height_cm') ?: null,
            'budget_minor' => $request->filled('budget') ? (int) round($request->float('budget') * 100) : null,
            'deadline' => $request->filled('deadline') ? $request->str('deadline')->toString() : null,
            'message' => $request->filled('message') ? $request->str('message')->toString() : null,
        ]);

        return redirect()
            ->route('commissions.show', $commission)
            ->with('status', __('commissions.request_sent'));
    }
}
