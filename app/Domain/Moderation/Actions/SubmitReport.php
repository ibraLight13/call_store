<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class SubmitReport
{
    public function handle(User $reporter, Model $reportable, string $reason, ?string $note): Report
    {
        if (! in_array($reason, Report::REASONS, true)) {
            throw new \InvalidArgumentException(__('reports.reason_invalid'));
        }

        return DB::transaction(function () use ($reporter, $reportable, $reason, $note): Report {
            return Report::query()->create([
                'reporter_id' => (int) $reporter->id,
                'reportable_type' => $reportable::class,
                'reportable_id' => $reportable->getKey(),
                'reason' => $reason,
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]);
        });
    }
}
