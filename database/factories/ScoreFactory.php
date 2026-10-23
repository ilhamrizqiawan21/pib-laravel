<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Score;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Score> */
class ScoreFactory extends Factory
{
    public function definition(): array
    {
        $mistakes = fake()->numberBetween(0, 10);

        return [
            'student_id' => Student::factory(),
            'assessment_id' => Assessment::factory(),
            'mistakes' => $mistakes,
            'score' => 90 - $mistakes,
            'assessed_at' => now(),
            'initials' => '',
            'note' => '',
            'version' => 1,
        ];
    }

    /** Baris yang pernah dinilai lalu dikosongkan (tetap ada demi versi). */
    public function cleared(): static
    {
        return $this->state(['mistakes' => null, 'score' => null, 'assessed_at' => null, 'version' => 2]);
    }
}
