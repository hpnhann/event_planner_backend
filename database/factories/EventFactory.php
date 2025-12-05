<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4), // Tên sự kiện giả (4 từ)
            'description' => $this->faker->paragraph(), // Mô tả giả
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'start_time' => '09:00:00',
            'location' => $this->faker->address(),
            'max_attendees' => $this->faker->numberBetween(10, 100),
            'status' => 'published',
            'user_id' => 99 // Vẫn gán cho ông User 99 huyền thoại
        ];
    }
}
