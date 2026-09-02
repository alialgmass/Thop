<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Subscriptions\Database\Seeders\SubscriptionPlanSeeder;
use Modules\Taxonomy\Database\Seeders\TaxonomyDatabaseSeeder;
use Modules\Verification\Database\Seeders\DocumentTypeSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TaxonomyDatabaseSeeder::class,
            DocumentTypeSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);
    }
}
