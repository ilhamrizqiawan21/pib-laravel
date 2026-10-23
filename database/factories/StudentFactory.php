<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'school_class_id' => SchoolClass::factory(),
            'nis' => (string) fake()->unique()->numerify('########'),
            'nisn' => (string) fake()->numerify('##########'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases()),
            'is_active' => true,
        ];
    }
}
