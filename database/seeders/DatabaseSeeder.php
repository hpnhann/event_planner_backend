<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Gọi seeder tạo sự kiện
        $this->call([
            EventSeeder::class,
        ]);
    }
}