<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tìm organizer user (hoặc tạo nếu chưa có)
        $organizer = User::whereHas('roles', function($q) {
            $q->where('name', 'organizer');
        })->first();

        // Nếu chưa có organizer, tạo mới
        if (!$organizer) {
            $organizer = User::create([
                'name' => 'Event Organizer',
                'email' => 'organizer@eventplanner.com',
                'password' => bcrypt('organizer123'),
            ]);
            
            $organizerRole = \App\Models\Role::where('name', 'organizer')->first();
            if ($organizerRole) {
                $organizer->roles()->attach($organizerRole->id);
            }
        }

        // Tạo events mẫu
        $events = [
            [
                'title' => 'Laravel Workshop 2025',
                'description' => 'Learn Laravel framework from basics to advanced',
                'start_date' => Carbon::now()->addDays(10)->setTime(9, 0, 0),
                'end_date' => Carbon::now()->addDays(10)->setTime(17, 0, 0),
                'location' => 'Hanoi University',
                'status' => 'published',
                'created_by' => $organizer->id,
                'max_participants' => 100,
            ],
            [
                'title' => 'React.js Bootcamp',
                'description' => 'Master React.js and build modern web applications',
                'start_date' => Carbon::now()->addDays(15)->setTime(9, 0, 0),
                'end_date' => Carbon::now()->addDays(15)->setTime(17, 0, 0),
                'location' => 'HCMC Tech Hub',
                'status' => 'published',
                'created_by' => $organizer->id,
                'max_participants' => 80,
            ],
            [
                'title' => 'AI & Machine Learning Conference',
                'description' => 'Explore the latest trends in AI and ML',
                'start_date' => Carbon::now()->addDays(20)->setTime(8, 0, 0),
                'end_date' => Carbon::now()->addDays(20)->setTime(18, 0, 0),
                'location' => 'Da Nang Convention Center',
                'status' => 'published',
                'created_by' => $organizer->id,
                'max_participants' => 200,
            ],
            [
                'title' => 'Mobile App Development Summit',
                'description' => 'Flutter and React Native workshop',
                'start_date' => Carbon::now()->addDays(30)->setTime(9, 0, 0),
                'end_date' => Carbon::now()->addDays(30)->setTime(16, 0, 0),
                'location' => 'Can Tho University',
                'status' => 'draft',
                'created_by' => $organizer->id,
                'max_participants' => 50,
            ],
        ];

        foreach ($events as $eventData) {
            Event::create($eventData);
        }

        $this->command->info('✅ Created ' . count($events) . ' sample events');
    }
}