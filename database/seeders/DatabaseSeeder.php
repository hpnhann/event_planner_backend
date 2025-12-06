<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {   
        // Chạy theo thứ tự: Roles -> Users -> Events
        $this->call([
            RoleSeeder::class,   // Tạo roles trước
            AdminSeeder::class,  // Tạo admin + organizer + participant
            EventSeeder::class,  // Tạo events mẫu
        ]);
    }
}