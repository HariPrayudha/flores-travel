<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'role' => 'admin',
            'password' => bcrypt('123')
        ]);

        User::create([
            'name' => 'Karani 1',
            'username' => 'karani1',
            'role' => 'karani',
            'password' => bcrypt('123')
        ]);

        User::create([
            'name' => 'Karani 2',
            'username' => 'karani2',
            'role' => 'karani',
            'password' => bcrypt('123')
        ]);

        $this->call(KotaSeeder::class);
    }
}
