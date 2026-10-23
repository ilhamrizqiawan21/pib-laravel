<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Subchapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subchapter> */
class SubchapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'title' => fake()->unique()->words(2, true),
            'display_order' => 0,
        ];
    }
}
