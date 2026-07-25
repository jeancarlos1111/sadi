<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            CatalogosBasicosSeeder::class,
            OnapreSeeder::class,
            OncopSeeder::class,
        ]);
    }
}
