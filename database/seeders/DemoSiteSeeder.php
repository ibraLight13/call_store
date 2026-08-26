<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Piece;
use App\Domain\Moderation\Models\Report;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the remaining features: purchase orders (paid/completed/refunded),
 * abuse reports in every triage state. Run after DemoPiecesSeeder.
 */
final class DemoSiteSeeder extends Seeder
{
    public function run(): void
    {
        $anas = User::query()->where('username', 'anas')->first();
        $salma = User::query()->where('username', 'salma')->first();
        $buyer = User::query()->where('username', 'abeer')->first();
        $admin = User::query()->where('username', 'admin')->first();

        if ($buyer === null || $anas === null || $salma === null || $admin === null) {
            return;
        }

        /** @var list<Piece> $anasPieces */
        $anasPieces = Piece::query()->where('seller_id', $anas->id)->orderBy('id')->get()->all();
        /** @var list<Piece> $salmaPieces */
        $salmaPieces = Piece::query()->where('seller_id', $salma->id)->orderBy('id')->get()->all();

        if (count($anasPieces) < 2 || count($salmaPieces) < 1) {
            return;
        }

        $pieceA = $anasPieces[0];
        $pieceSalma = $salmaPieces[0];

        $address = [
            'recipient_name' => 'عبير م.',
            'phone' => '0555 123 456',
            'country' => 'SA',
            'city' => 'الرياض',
            'district' => 'حي الملقا',
            'street' => 'شارع أنس بن مالك',
            'building' => '3245',
            'postal_code' => '13521',
        ];

        // ── Order 1: paid, one seller group, shipped ─────────────────
        $order = Order::query()->create([
            'buyer_id' => $buyer->id,
            'subtotal_minor' => 145_000,
            'shipping_minor' => 4_000,
            'total_minor' => 149_000,
            'address' => $address,
            'gateway' => 'fake',
            'checkout_ref' => 'chk_demo_1',
            'payment_ref' => 'pay_demo_1',
            'placed_at' => now()->subDays(6),
            'paid_at' => now()->subDays(6),
            'state' => 'paid',
        ]);

        DB::table('order_sellers')->insert([
            'order_id' => $order->id,
            'seller_id' => $anas->id,
            'subtotal_minor' => 145_000,
            'shipping_minor' => 4_000,
            'transfer_status' => 'transferred',
            'transfer_ref' => 'tr_demo_o1',
            'transferred_at' => now()->subDays(6),
            'shipped_at' => now()->subDays(2),
            'tracking_number' => 'SA-55821',
        ]);

        DB::table('order_items')->insert([
            'order_id' => $order->id,
            'seller_id' => $anas->id,
            'piece_id' => $pieceA->id,
            'text' => $pieceA->text,
            'price_minor' => 145_000,
            'currency' => 'SAR',
            'quantity' => 1,
        ]);

        DB::table('pieces')->where('id', $pieceA->id)->update(['sold_at' => now()->subDays(6)]);

        // ── Order 2: completed, two seller groups ────────────────────
        $order = Order::query()->create([
            'buyer_id' => $buyer->id,
            'subtotal_minor' => 207_000,
            'shipping_minor' => 8_000,
            'total_minor' => 215_000,
            'address' => $address,
            'gateway' => 'fake',
            'checkout_ref' => 'chk_demo_2',
            'payment_ref' => 'pay_demo_2',
            'placed_at' => now()->subDays(20),
            'paid_at' => now()->subDays(20),
            'completed_at' => now()->subDays(9),
            'state' => 'completed',
        ]);

        foreach ([$anas, $salma] as $i => $seller) {
            $subtotal = $i === 0 ? 145_000 : 62_000;

            DB::table('order_sellers')->insert([
                'order_id' => $order->id,
                'seller_id' => (int) $seller->id,
                'subtotal_minor' => $subtotal,
                'shipping_minor' => 4_000,
                'transfer_status' => 'transferred',
                'transfer_ref' => 'tr_demo_o2_'.$seller->id,
                'transferred_at' => now()->subDays(20),
                'shipped_at' => now()->subDays(14),
                'tracking_number' => 'SA-100'.$i,
            ]);
        }

        DB::table('order_items')->insert([
            [
                'order_id' => $order->id,
                'seller_id' => (int) $anas->id,
                'piece_id' => $pieceA->id,
                'text' => $pieceA->text,
                'price_minor' => 145_000,
                'currency' => 'SAR',
                'quantity' => 1,
            ],
            [
                'order_id' => $order->id,
                'seller_id' => (int) $salma->id,
                'piece_id' => $pieceSalma->id,
                'text' => $pieceSalma->text,
                'price_minor' => 62_000,
                'currency' => 'SAR',
                'quantity' => 1,
            ],
        ]);

        // ── Order 3: refunded ────────────────────────────────────────
        $order = Order::query()->create([
            'buyer_id' => $buyer->id,
            'subtotal_minor' => 62_000,
            'shipping_minor' => 4_000,
            'total_minor' => 66_000,
            'address' => $address,
            'gateway' => 'fake',
            'checkout_ref' => 'chk_demo_3',
            'payment_ref' => 'pay_demo_3',
            'placed_at' => now()->subDays(30),
            'paid_at' => now()->subDays(30),
            'refunded_at' => now()->subDays(25),
            'state' => 'refunded',
        ]);

        DB::table('order_items')->insert([
            'order_id' => $order->id,
            'seller_id' => (int) $salma->id,
            'piece_id' => $pieceSalma->id,
            'text' => $pieceSalma->text,
            'price_minor' => 62_000,
            'currency' => 'SAR',
            'quantity' => 1,
        ]);

        // ── Abuse reports across triage states ───────────────────────
        Report::query()->create([
            'reporter_id' => $buyer->id,
            'reportable_type' => $pieceA::class,
            'reportable_id' => $pieceA->id,
            'reason' => 'copyright',
            'note' => 'هذه القطعة منسوخة من عمل نشره فنّان آخر بلا إذن.',
            'status' => 'pending',
        ]);

        Report::query()->create([
            'reporter_id' => $buyer->id,
            'reportable_type' => $salma::class,
            'reportable_id' => $salma->id,
            'reason' => 'scam',
            'note' => 'تطلب التواصل خارج المنصة والدفع المباشر.',
            'status' => 'pending',
        ]);

        Report::query()->create([
            'reporter_id' => $buyer->id,
            'reportable_type' => $anas::class,
            'reportable_id' => $anas->id,
            'reason' => 'other',
            'note' => 'بلاغ خاطئ — تم التحقق.',
            'status' => 'dismissed',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subDays(2),
        ]);
    }
}
