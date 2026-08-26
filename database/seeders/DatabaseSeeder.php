<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'admin@khatt.test'],
            ['name' => 'مشرف المنصّة', 'username' => 'admin', 'password' => 'password'],
        )->assignRole('admin');

        User::query()->updateOrCreate(
            ['email' => 'anas@khatt.test'],
            [
                'name' => 'أستاذ أنس الخطّاط',
                'username' => 'anas',
                'bio' => 'خطّاط سوري، إجازة في خطّي الثلث والديواني. أكتب الآيات والأسماء بأعلى معايير الخط العربي الأصيل.',
                'password' => 'password',
            ],
        )->assignRole('seller');

        User::query()->updateOrCreate(
            ['email' => 'salma@khatt.test'],
            [
                'name' => 'سلمى للخط والزخرفة',
                'username' => 'salma',
                'bio' => 'فنّانة زخرفة وخط ديواني وجلي، أعمل على قطع الزفاف والهدايا الفاخرة.',
                'password' => 'password',
            ],
        )->assignRole('seller');

        User::query()->updateOrCreate(
            ['email' => 'abeer@khatt.test'],
            ['name' => 'عبير م.', 'username' => 'abeer', 'password' => 'password'],
        )->assignRole('buyer');

        $this->call(CatalogSeeder::class);
        $this->call(DemoPiecesSeeder::class);
        $this->call(DemoSiteSeeder::class);
    }
}
