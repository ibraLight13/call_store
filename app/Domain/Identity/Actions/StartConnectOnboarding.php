<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class StartConnectOnboarding
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function handle(User $seller, string $returnUrl): string
    {
        if (! $seller->hasRole('seller') && ! $seller->hasRole('admin')) {
            throw new AuthorizationException;
        }

        $onboarding = $this->gateway->createConnectOnboarding($seller, $returnUrl);

        User::query()->whereKey($seller->id)->update([
            'stripe_account_id' => $onboarding->accountId,
        ]);

        return $onboarding->redirectUrl;
    }
}
