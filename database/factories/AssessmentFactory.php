<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Subchapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Assessment> */
class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subchapter_id' => Subchapter::factory(),
            'title' => fake()->unique()->sentence(3),
            'description' => null,
            'weight' => 1,
            'display_order' => 0,
            'is_active' => true,
        ];
    }

    public function weight(float $weight): static
    {
        return $this->state(['weight' => $weight]);
    }
}
