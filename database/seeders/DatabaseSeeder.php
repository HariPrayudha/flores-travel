<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(KotaSeeder::class);

        $defaultKota = \App\Models\Kota::first();

        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'role' => 'admin',
            'password' => bcrypt('123'),
            'gambar' => null,
            'kota_id' => $defaultKota?->id,
        ]);

        User::create([
            'name' => 'Admin 2',
            'username' => 'admin2',
            'role' => 'admin',
            'password' => bcrypt('123'),
            'gambar' => null,
            'kota_id' => $defaultKota?->id,
        ]);

        User::create([
            'name' => 'Karani 1',
            'username' => 'karani1',
            'role' => 'karani',
            'password' => bcrypt('123'),
            'gambar' => null,
            'kota_id' => $defaultKota?->id,
        ]);

        User::create([
            'name' => 'Karani 2',
            'username' => 'karani2',
            'role' => 'karani',
            'password' => bcrypt('123'),
            'gambar' => null,
            'kota_id' => $defaultKota?->id,
        ]);
    }
}
