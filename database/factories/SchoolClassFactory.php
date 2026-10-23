<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SchoolClass> */
class SchoolClassFactory extends Factory
{
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => fake()->unique()->bothify('#?'),
            'grade_level' => (string) fake()->numberBetween(7, 9),
            'homeroom_teacher' => fake()->name(),
            'is_active' => true,
        ];
    }
}
