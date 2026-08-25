<?php

namespace App\Http\Controllers\Commissions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Social\Actions\CreateReview;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ReviewController extends Controller
{
    use ResolvesActor;

    public function store(Request $request, Commission $commission): RedirectResponse
    {
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(CreateReview::class)->handle(
                buyer: $this->actor($request),
                commission: $commission,
                rating: (int) $request->integer('rating'),
                body: $request->filled('body') ? $request->str('body')->toString() : null,
            );
        } catch (\DomainException|AuthorizationException $e) {
            if ($e instanceof AuthorizationException) {
                throw $e;
            }

            throw ValidationException::withMessages(['rating' => $e->getMessage()]);
        }

        return back()->with('status', __('reviews.submitted'));
    }
}
