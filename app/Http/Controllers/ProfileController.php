<?php

namespace App\Http\Controllers;

use App\Domain\Social\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function show(Request $request, User $user): View
    {
        $viewer = $request->user();

        $user->loadCount(['followers', 'following']);

        $reviews = Review::query()
            ->where('seller_id', $user->id)
            ->with('buyer:id,name,username')
            ->latest()
            ->limit(10)
            ->get();

        $stats = DB::table('reviews')
            ->where('seller_id', $user->id)
            ->selectRaw('COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total')
            ->first();

        $avgRating = 0.0;
        $reviewsTotal = 0;

        if ($stats !== null) {
            if (is_numeric($stats->avg_rating)) {
                $avgRating = (float) $stats->avg_rating;
            }

            if (is_numeric($stats->total)) {
                $reviewsTotal = (int) $stats->total;
            }
        }

        return view('pages.profile', [
            'profile' => $user,
            'isSelf' => $viewer !== null && $viewer->is($user),
            'isFollowing' => $viewer !== null && $viewer->isFollowing($user),
            'reviews' => $reviews,
            'avgRating' => $avgRating,
            'reviewsTotal' => $reviewsTotal,
        ]);
    }
}
