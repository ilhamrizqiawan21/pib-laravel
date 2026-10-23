<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\CurriculumTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CurriculumTemplate> */
class CurriculumTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
