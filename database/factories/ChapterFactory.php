<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Chapter> */
class ChapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'curriculum_template_id' => CurriculumTemplate::factory(),
            'title' => fake()->unique()->words(2, true),
            'display_order' => 0,
        ];
    }
}
