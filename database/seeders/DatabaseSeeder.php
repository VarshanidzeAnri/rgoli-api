<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        if (app()->environment(['local', 'testing']) || (bool) config('rgoli.demo')) {
            $this->call(DemoSeeder::class);
        }
    }
}
