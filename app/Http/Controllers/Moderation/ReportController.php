<?php

namespace App\Http\Controllers\Moderation;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Moderation\Actions\SubmitReport;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class ReportController extends Controller
{
    use ResolvesActor;

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'reportable_type' => ['required', 'in:piece,user'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'in:inappropriate,offensive,copyright,scam,other'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $subject = ($request->input('reportable_type') === 'piece')
            ? Piece::query()->find(self::intId($request->input('reportable_id')))
            : User::query()->find(self::intId($request->input('reportable_id')));

        if (! $subject instanceof Model) {
            return back()->with('error', __('reports.reason_invalid'));
        }

        $reason = $request->str('reason')->toString();
        $note = $request->filled('note') ? $request->str('note')->toString() : null;
        $actor = $this->actor($request);

        $executed = RateLimiter::attempt(
            key: 'report:'.$actor->id,
            maxAttempts: 5,
            callback: function () use ($actor, $subject, $reason, $note): void {
                app(SubmitReport::class)->handle($actor, $subject, $reason, $note);
            },
            decaySeconds: 3600,
        );

        if ($executed === false) {
            return back()->with('error', __('reports.rate_limited'));
        }

        return back()->with('status', __('reports.received'));
    }

    private static function intId(mixed $value): int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return 0;
    }
}
