<?php

namespace App\Http\Controllers\Commissions;

use App\Domain\Commissions\Models\Commission;
use App\Http\Concerns\ResolvesActor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CommissionController extends Controller
{
    use ResolvesActor;

    public function index(Request $request): View
    {
        $actor = $this->actor($request);

        $commissions = Commission::query()
            ->where(fn ($q) => $q->where('buyer_id', $actor->id)->orWhere('seller_id', $actor->id))
            ->with(['buyer:id,name,username', 'seller:id,name,username'])
            ->latest('id')
            ->paginate(15);

        return view('pages.commissions', [
            'commissions' => $commissions,
            'actorId' => (int) $actor->id,
        ]);
    }

    public function show(Request $request, Commission $commission): View
    {
        $actor = $this->actor($request);

        Gate::authorize('view', $commission);

        $commission->load(['buyer:id,name,username', 'seller:id,name,username', 'script', 'medium', 'proofs', 'messages.user:id,name,username']);

        $isBuyer = (int) $commission->buyer_id === (int) $actor->id;
        $stateKey = $commission->state::getMorphClass();

        // Ordered lifecycle for the timeline sidebar.
        $lifecycle = [
            'requested' => 1, 'quoted' => 2, 'deposit_paid' => 3, 'in_progress' => 4,
            'proof_submitted' => 5, 'revision_requested' => 5, 'approved' => 6,
            'balance_paid' => 7, 'shipped' => 8, 'completed' => 9,
        ];
        $current = $lifecycle[$stateKey] ?? 0;

        return view('pages.commission', [
            'commission' => $commission,
            'isBuyer' => $isBuyer,
            'stateKey' => $stateKey,
            'currentStep' => $current,
            'latestProof' => $commission->proofs->first(),
        ]);
    }
}
