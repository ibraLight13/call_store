<?php

namespace App\Domain\Commissions\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\Models\CommissionProof;
use App\Domain\Commissions\States\InProgress;
use App\Domain\Commissions\States\ProofSubmitted;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class SubmitCommissionProof
{
    public function __construct(private readonly NotifyCounterparty $notify) {}

    public function handle(User $seller, Commission $commission, UploadedFile $image, ?string $note): CommissionProof
    {
        Gate::forUser($seller)->authorize('manageAsSeller', $commission);

        $state = $commission->state::getMorphClass();

        if (! in_array($state, ['deposit_paid', 'in_progress', 'revision_requested'], true)) {
            throw new \DomainException(__('commissions.proof_not_submittable'));
        }

        return DB::transaction(function () use ($seller, $commission, $image, $note, $state): CommissionProof {
            if ($state === 'deposit_paid') {
                $commission->state->transitionTo(InProgress::class);
                $commission->save();
            }

            if ($state === 'revision_requested') {
                $commission->increment('revision_count');
            }

            $version = (int) $commission->proofs()->count() + 1;

            $contents = file_get_contents($image->getRealPath());

            if ($contents === false) {
                throw new \RuntimeException(__('commissions.proof_not_submittable'));
            }

            $path = 'commissions/'.$commission->id.'/'.Str::uuid().'.jpg';
            Storage::disk('local')->put($path, $contents);

            $proof = CommissionProof::query()->create([
                'commission_id' => $commission->id,
                'seller_id' => $seller->id,
                'version' => $version,
                'image_path' => $path,
                'seller_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]);

            // From in_progress or revision_requested → proof_submitted.
            $freshCommission = $commission->fresh();

            if ($freshCommission === null || $freshCommission->state::getMorphClass() !== 'proof_submitted') {
                $commission->state->transitionTo(ProofSubmitted::class);
                $commission->save();
            }

            $this->notify->handle($commission, 'buyer', 'proof_submitted');

            return $proof;
        });
    }
}
