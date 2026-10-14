<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            CompanySeeder::class,
            ManagerSeeder::class,
            JobPostingSeeder::class,
            AccountSeeder::class,
        ]);
    }
}
