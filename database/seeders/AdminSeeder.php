<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tạo Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@eventplanner.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
            ]
        );

        // Gán role admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$admin->roles()->where('role_id', $adminRole->id)->exists()) {
            $admin->roles()->attach($adminRole->id);
        }

        // 2. Tạo Organizer User
        $organizer = User::firstOrCreate(
            ['email' => 'organizer@eventplanner.com'],
            [
                'name' => 'Event Organizer',
                'password' => Hash::make('organizer123'),
            ]
        );

        // Gán role organizer
        $organizerRole = Role::where('name', 'organizer')->first();
        if ($organizerRole && !$organizer->roles()->where('role_id', $organizerRole->id)->exists()) {
            $organizer->roles()->attach($organizerRole->id);
        }

        // 3. Tạo Participant User
        $participant = User::firstOrCreate(
            ['email' => 'participant@eventplanner.com'],
            [
                'name' => 'Participant User',
                'password' => Hash::make('participant123'),
            ]
        );

        // Gán role participant
        $participantRole = Role::where('name', 'participant')->first();
        if ($participantRole && !$participant->roles()->where('role_id', $participantRole->id)->exists()) {
            $participant->roles()->attach($participantRole->id);
        }

        $this->command->info('✅ Created Admin, Organizer, and Participant users');
    }
}