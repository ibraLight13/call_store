<?php

use App\Domain\Catalog\Models\Piece;
use App\Domain\Identity\Actions\FollowUser;
use App\Domain\Identity\Notifications\NewFollower;
use App\Domain\Social\Actions\AddComment;
use App\Domain\Social\Actions\DeleteComment;
use App\Domain\Social\Actions\LikePiece;
use App\Domain\Social\Actions\SaveToBoard;
use App\Domain\Social\Actions\UnlikePiece;
use App\Domain\Social\Actions\UnsaveFromBoard;
use App\Domain\Social\Models\Board;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Notifications\CommentAdded;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;

function socialPiece(User $seller): Piece
{
    return Piece::query()->create([
        'seller_id' => $seller->id,
        'text' => 'الحَمدُ لِلَّه',
        'normalized_text' => 'الحمد لله',
        'text_language' => 'ar',
        'listing_type' => 'ready',
        'price_minor' => 100_000,
        'state' => 'approved',
        'published_at' => now(),
    ]);
}

it('likes idempotently and keeps the denormalized counter exact', function () {
    [$a, $b] = User::factory()->count(2)->create();
    $piece = socialPiece($a);

    app(LikePiece::class)->handle($a, $piece);
    app(LikePiece::class)->handle($a, $piece); // double-fire (double click)
    app(LikePiece::class)->handle($b, $piece);

    expect((int) $piece->fresh()->likes_count)->toBe(2);

    app(UnlikePiece::class)->handle($a, $piece);
    expect((int) $piece->fresh()->likes_count)->toBe(1);

    // Unlike again must not drive the counter below zero.
    app(UnlikePiece::class)->handle($a, $piece);
    expect((int) $piece->fresh()->likes_count)->toBe(1);
});

it('counts a save once per user across boards', function () {
    [$user] = User::factory()->count(1)->create();
    $seller = User::factory()->create();
    $piece = socialPiece($seller);

    $boardOne = Board::query()->create(['user_id' => $user->id, 'name' => 'لوحة أولى']);
    $boardTwo = Board::query()->create(['user_id' => $user->id, 'name' => 'لوحة ثانية']);

    app(SaveToBoard::class)->handle($user, $boardOne, $piece);
    app(SaveToBoard::class)->handle($user, $boardTwo, $piece);

    expect($boardOne->fresh()->pieces()->count())->toBe(1);
    expect($boardTwo->fresh()->pieces()->count())->toBe(1);
    expect((int) $piece->fresh()->saves_count)->toBe(1); // one user = one save

    app(UnsaveFromBoard::class)->handle($user, $boardOne, $piece);
    expect((int) $piece->fresh()->saves_count)->toBe(1); // still on board two

    app(UnsaveFromBoard::class)->handle($user, $boardTwo, $piece);
    expect((int) $piece->fresh()->saves_count)->toBe(0);
});

it('forbids saving into someone else board', function () {
    [$owner, $intruder] = User::factory()->count(2)->create();
    $seller = User::factory()->create();

    $board = Board::query()->create(['user_id' => $owner->id, 'name' => 'خاصة']);
    $piece = socialPiece($seller);

    expect(fn () => app(SaveToBoard::class)->handle($intruder, $board, $piece))
        ->toThrow(AuthorizationException::class);
});

it('adds comments notifies the artist and supports one level of replies', function () {
    Notification::fake();

    [$commenter, $replier] = User::factory()->count(2)->create();
    $seller = User::factory()->create();
    $piece = socialPiece($seller);

    Notification::assertNothingSent();

    $topLevel = app(AddComment::class)->handle($commenter, $piece, 'تحفة فنية!');

    Notification::assertSentTo(
        $seller,
        CommentAdded::class,
        fn ($n) => $n->context === 'piece',
    );

    $reply = app(AddComment::class)->handle($seller, $piece, 'شكرًا لك!', parentId: $topLevel->id);

    Notification::assertSentTo(
        $commenter,
        CommentAdded::class,
        fn ($n) => $n->context === 'reply',
    );

    // Replying to a reply is beyond one level: rejected.
    expect(fn () => app(AddComment::class)->handle($replier, $piece, 'عمق زائد', parentId: $reply->id))
        ->toThrow(InvalidArgumentException::class);
});

it('does not notify the actor about their own comment', function () {
    Notification::fake();

    $seller = User::factory()->create();
    $piece = socialPiece($seller);

    app(AddComment::class)->handle($seller, $piece, 'أول تعليق من صاحب القطعة');

    Notification::assertNotSentTo($seller, CommentAdded::class);
});

it('notifies the followed user when someone follows them', function () {
    Notification::fake();
    [$a, $b] = User::factory()->count(2)->create();

    app(FollowUser::class)->handle($a, $b);
    app(FollowUser::class)->handle($a, $b); // idempotent

    Notification::assertSentToTimes($b, NewFollower::class, 1);
});

it('rate limits comment writes', function () {
    $this->seed(DatabaseSeeder::class);

    $commenter = User::factory()->create();
    $seller = User::factory()->create();
    $piece = socialPiece($seller);

    $this->actingAs($commenter);

    for ($i = 0; $i < 30; $i++) {
        $response = $this->post(route('comments.store', $piece), ['body' => "تعليق رقم $i"]);
        $response->assertStatus(302);
    }

    $this->post(route('comments.store', $piece), ['body' => 'واحد فوق الحد'])
        ->assertStatus(429);
});

it('lets owners and admins delete comments and cleans replies', function () {
    [$author] = User::factory()->count(1)->create();
    $seller = User::factory()->create();
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $piece = socialPiece($seller);
    $top = app(AddComment::class)->handle($author, $piece, 'تعليق سيُحذف');
    app(AddComment::class)->handle($seller, $piece, 'رد على التعليق', parentId: $top->id);

    app(DeleteComment::class)->handle($admin, $top);

    expect(Comment::query()->where('piece_id', $piece->id)->count())->toBe(0);
});
