<?php

namespace Database\Seeders;

use App\Models\Kota;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KotaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kota::create([
            'nama' => 'Sibolga'
        ]);

        Kota::create([
            'nama' => 'Pandan'
        ]);

        Kota::create([
            'nama' => 'Medan'
        ]);
    }
}
