<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicYear> */
class AcademicYearFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2000, 2099);

        return [
            'name' => $year.'/'.($year + 1),
            'semester' => Semester::Ganjil,
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function genap(): static
    {
        return $this->state(['semester' => Semester::Genap]);
    }
}
