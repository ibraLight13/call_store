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
            ['name' => 'مشرف المنصّة', 'password' => 'password'],
        )->assignRole('admin');

        User::query()->updateOrCreate(
            ['email' => 'anas@khatt.test'],
            ['name' => 'أستاذ أنس الخطّاط', 'password' => 'password'],
        )->assignRole('seller');

        User::query()->updateOrCreate(
            ['email' => 'salma@khatt.test'],
            ['name' => 'سلمى للخط والزخرفة', 'password' => 'password'],
        )->assignRole('seller');

        User::query()->updateOrCreate(
            ['email' => 'abeer@khatt.test'],
            ['name' => 'عبير م.', 'password' => 'password'],
        )->assignRole('buyer');
    }
}
