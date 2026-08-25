<?php

namespace App\Domain\Social\Actions;

use App\Domain\Commissions\Models\Commission;
use App\Domain\Social\Models\Review;
use App\Domain\Social\Notifications\ReviewReceived;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class CreateReview
{
    public function handle(User $buyer, Commission $commission, int $rating, ?string $body): Review
    {
        if ((int) $commission->buyer_id !== (int) $buyer->id) {
            throw new AuthorizationException;
        }

        if ($commission->state::getMorphClass() !== 'completed') {
            throw new DomainException(__('reviews.only_completed'));
        }

        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException(__('reviews.rating_invalid'));
        }

        $body = trim((string) $body);

        return DB::transaction(function () use ($buyer, $commission, $rating, $body): Review {
            $review = Review::query()->firstOrCreate(
                ['commission_id' => $commission->id],
                [
                    'buyer_id' => $buyer->id,
                    'seller_id' => $commission->seller_id,
                    'rating' => $rating,
                    'body' => $body !== '' ? $body : null,
                ],
            );

            if (! $review->wasRecentlyCreated) {
                throw new DomainException(__('reviews.already_reviewed'));
            }

            $review->setRelations([
                'buyer' => $buyer,
                'seller' => $commission->seller,
                'commission' => $commission,
            ]);

            Notification::send(
                User::query()->whereKey($commission->seller_id)->get(),
                new ReviewReceived($review),
            );

            return $review;
        });
    }
}
