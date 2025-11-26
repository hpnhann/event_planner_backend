<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 10 sự kiện mẫu
        for ($i = 1; $i <= 10; $i++) {
            Event::create([
                'title' => 'Sự kiện Công nghệ Demo #' . $i,
                'description' => 'Đây là mô tả mẫu cho sự kiện số ' . $i . '. Học lập trình Laravel rất vui!',
                'start_date' => Carbon::now()->addDays($i), // Ngày bắt đầu tăng dần
                'start_time' => '09:00:00',
                'location' => 'Hội trường ' . ($i % 2 == 0 ? 'A' : 'B'), // Chẵn hội trường A, lẻ hội trường B
                'max_attendees' => 50 + ($i * 10), // Số lượng vé khác nhau
                'status' => 'published',
                'user_id' => 99 // Gán cho ông User 99 mình tạo hôm qua
            ]);
        }
    }
}