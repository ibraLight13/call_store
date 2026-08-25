<?php

use App\Domain\Commissions\Actions\ApproveCommissionProof;
use App\Domain\Commissions\Actions\CompleteCommission;
use App\Domain\Commissions\Actions\PayCommissionCapture;
use App\Domain\Commissions\Actions\QuoteCommission;
use App\Domain\Commissions\Actions\RequestCommission;
use App\Domain\Commissions\Actions\RequestCommissionRevision;
use App\Domain\Commissions\Actions\ShipCommission;
use App\Domain\Commissions\Actions\StartCommissionWork;
use App\Domain\Commissions\Actions\SubmitCommissionProof;
use App\Domain\Commissions\Models\CommissionProof;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

function startCommission(): array
{
    test()->seed(DatabaseSeeder::class);

    $buyer = User::factory()->create();
    $seller = User::factory()->create();
    $seller->assignRole('seller');
    $seller->forceFill(['stripe_account_id' => 'acct_'.$seller->id, 'stripe_charges_enabled' => true])->save();

    $commission = app(RequestCommission::class)->handle($buyer, $seller, [
        'text' => 'بَيتٌ يَسَعُهُ الخَير',
        'text_language' => 'ar',
        'width_cm' => 60,
        'height_cm' => 40,
    ]);

    return [$buyer, $seller, $commission];
}

it('advances the full lifecycle with money released only on approval', function () {
    Notification::fake();
    Storage::fake('local');

    [$buyer, $seller, $commission] = startCommission();

    expect($commission->state::getMorphClass())->toBe('requested');

    // Seller quotes 1,800 SAR → deposit = 30% = 540.
    app(QuoteCommission::class)->handle($seller, $commission->fresh(), 180_000, 'التسليم خلال ١٢ يومًا');
    $commission = $commission->fresh();

    expect($commission->state::getMorphClass())->toBe('quoted')
        ->and($commission->quote_minor)->toBe(180_000)
        ->and($commission->deposit_minor)->toBe(54_000)
        ->and($commission->transfer_ref)->toBeNull(); // no money moved yet

    // Buyer pays the deposit through the idempotent capture.
    $capture = app(PayCommissionCapture::class);
    $capture->handle($buyer, $commission->fresh(), 'deposit', 'evt_dep_1', 'pay_cm_dep_1');

    $commission = $commission->fresh();
    expect($commission->state::getMorphClass())->toBe('deposit_paid')
        ->and($commission->deposit_payment_ref)->toBe('pay_cm_dep_1');

    // Replay must be a no-op.
    $again = $capture->handle($buyer, $commission, 'deposit', 'evt_dep_1', 'pay_cm_dep_1');
    expect($again->state::getMorphClass())->toBe('deposit_paid');

    // Balance cannot be paid before approval — the machine refuses it.
    expect(fn () => $capture->handle($buyer, $commission, 'balance', 'evt_bal_early', 'pay_cm_bal_early'))
        ->toThrow(DomainException::class);

    // Seller starts work and submits proof v1.
    app(StartCommissionWork::class)->handle($seller, $commission->fresh());
    app(SubmitCommissionProof::class)->handle(
        $seller, $commission->fresh(), UploadedFile::fake()->image('proof1.jpg', 800, 600), 'النسخة الأولى',
    );

    $commission = $commission->fresh();
    expect($commission->state::getMorphClass())->toBe('proof_submitted')
        ->and($commission->proofs()->count())->toBe(1);

    // Buyer requests a revision; seller submits v2; buyer approves.
    app(RequestCommissionRevision::class)->handle(
        $buyer, $commission, 'ميلان الألف يحتاج تعديلًا',
    );

    // revision_count increments when the corrected proof lands.
    app(SubmitCommissionProof::class)->handle(
        $seller, $commission->fresh(), UploadedFile::fake()->image('proof2.jpg', 800, 600), 'عدّلت الميلان',
    );

    $commission = $commission->fresh();
    expect($commission->proofs()->count())->toBe(2)
        ->and((int) $commission->revision_count)->toBe(1);

    app(ApproveCommissionProof::class)->handle($buyer, $commission);
    expect($commission->fresh()->state::getMorphClass())->toBe('approved');

    // Balance payment releases the transfer — post-approval by design.
    $capture->handle($buyer, $commission->fresh(), 'balance', 'evt_bal_1', 'pay_cm_bal_1');

    $commission = $commission->fresh();
    expect($commission->state::getMorphClass())->toBe('balance_paid')
        ->and($commission->transfer_status)->toBe('transferred')
        ->and($commission->transfer_ref)->toBe('tr_fake_cm_'.$commission->id.'_'.$seller->id);

    // Ship then complete.
    app(ShipCommission::class)->handle($seller, $commission->fresh(), 'SA-777');
    app(CompleteCommission::class)->handle($buyer, $commission->fresh());

    $final = $commission->fresh();
    expect($final->state::getMorphClass())->toBe('completed')
        ->and($final->tracking_number)->toBe('SA-777');

    // Ledger: deposit + balance settled exactly once each.
    expect(DB::table('processed_webhooks')->count())->toBe(3); // dep, replay(dep), balance
});

it('rejects every illegal shortcut in the machine', function () {
    Notification::fake();
    Storage::fake('local');

    [$buyer, $seller, $commission] = startCommission();

    // Approve before any proof exists.
    expect(fn () => app(ApproveCommissionProof::class)->handle($buyer, $commission))
        ->toThrow(DomainException::class);

    // Quote then try shipping immediately (no deposit/proof/balance).
    app(QuoteCommission::class)->handle($seller, $commission->fresh(), 100_000);
    $commission = $commission->fresh();

    expect(fn () => app(ShipCommission::class)->handle($seller, $commission))
        ->toThrow(DomainException::class);

    // Deposit paid: balance still locked until approval.
    app(PayCommissionCapture::class)->handle($buyer, $commission, 'deposit', 'evt_g1', 'ref_g1');

    expect(fn () => app(PayCommissionCapture::class)->handle($buyer, $commission->fresh(), 'balance', 'evt_g2', 'ref_g2'))
        ->toThrow(DomainException::class);

    // Revision request before any proof is illegal too.
    expect(fn () => app(RequestCommissionRevision::class)->handle($buyer, $commission->fresh(), 'غيّر شيئًا'))
        ->toThrow(DomainException::class);
});

it('guards workspace access and seller-only actions', function () {
    [$buyer, $seller, $commission] = startCommission();

    $outsider = User::factory()->create();

    // Outsider cannot see the workspace or act on it.
    $this->actingAs($outsider)->get(route('commissions.show', $commission))->assertForbidden();
    $this->actingAs($outsider)->post(route('commissions.quote', $commission), ['quote' => '500'])
        ->assertForbidden();

    // Only the buyer may approve.
    expect(fn () => app(ApproveCommissionProof::class)->handle($seller, $commission))
        ->toThrow(AuthorizationException::class);

    // The buyer CAN view it.
    $this->actingAs($buyer)->get(route('commissions.show', $commission))
        ->assertOk()
        ->assertSee($commission->number);
});

it('serves proofs only to the two parties from private storage', function () {
    Storage::fake('local');
    [$buyer, $seller, $commission] = startCommission();

    $proof = CommissionProof::query()->create([
        'commission_id' => $commission->id,
        'seller_id' => $seller->id,
        'version' => 1,
        'image_path' => 'commissions/'.$commission->id.'/proof.jpg',
    ]);

    Storage::disk('local')->put($proof->image_path, 'jpeg-bytes');

    $this->actingAs($buyer)->get(route('commissions.proof', [$commission, $proof]))->assertOk();
    $this->actingAs($seller)->get(route('commissions.proof', [$commission, $proof]))->assertOk();

    $outsider = User::factory()->create();
    $this->actingAs($outsider)->get(route('commissions.proof', [$commission, $proof]))->assertForbidden();
});
