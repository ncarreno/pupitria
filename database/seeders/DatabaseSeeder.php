<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Artisan::call('curriculo:importar', [], $this->command?->getOutput());
        $this->call(IaConfiguracionSeeder::class);
    }
}
