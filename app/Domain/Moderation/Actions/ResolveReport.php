<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Catalog\States\Rejected;
use App\Domain\Moderation\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ResolveReport
{
    /**
     * Admin triage: mark a report resolved or dismissed. When resolving a
     * reported piece, the piece goes back to moderation (rejected).
     */
    public function handle(User $admin, Report $report, string $decision, ?string $note = null): Report
    {
        if (! $admin->hasRole('admin')) {
            throw new AuthorizationException;
        }

        if (! in_array($decision, ['resolved', 'dismissed'], true)) {
            throw new \InvalidArgumentException(__('reports.decision_invalid'));
        }

        return DB::transaction(function () use ($admin, $report, $decision, $note): Report {
            $takedown = $decision === 'resolved'
                && $report->reportable instanceof Piece;

            $report->update([
                'status' => $decision,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'note' => $note !== null && trim($note) !== '' ? trim($note) : $report->note,
            ]);

            if ($takedown) {
                /** @var Piece $piece */
                $piece = $report->reportable;

                if (in_array($piece->state::getMorphClass(), ['approved', 'pending_review'], true)) {
                    $piece->state->transitionTo(Rejected::class);
                    $piece->rejection_reason = __('reports.takedown_reason');
                    $piece->save();
                }
            }

            return $report;
        });
    }
}
