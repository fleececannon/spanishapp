<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // NOTE: Words have no seeder on purpose. The words library lives in the
        // production database and is managed there directly (admin UI or direct
        // inserts) — the production admin's unlock/vocab toggles are the source
        // of truth, and re-seeding from a file would overwrite them. A fresh
        // local install starts with an empty words library.
        $this->call([
            AdminUserSeeder::class,
            KidSeeder::class,
            SettingsSeeder::class,
            VerbSeeder::class,
            PatternSeeder::class,
        ]);
    }
}
