<?php

namespace App\Http\Controllers\Commissions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Commissions\Models\CommissionProof;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CommissionProofController extends Controller
{
    use ResolvesActor;

    /**
     * Proofs are confidential between the two parties — streamed from the
     * private disk through an authorization gate, never from public storage.
     */
    public function show(Request $request, Commission $commission, CommissionProof $proof): StreamedResponse
    {
        Gate::authorize('view', $commission);

        if ((int) $proof->commission_id !== (int) $commission->id) {
            abort(404);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($proof->image_path)) {
            abort(404);
        }

        return $disk->response($proof->image_path, 'proof-v'.$proof->version.'.jpg', ['Content-Type' => 'image/jpeg']);
    }
}
