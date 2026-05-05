<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Publisher;

class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'isbn' => $this->faker->unique()->isbn13(),
            'name' => $this->faker->sentence(3),
            'publisher_id' => Publisher::inRandomOrder()->first()?->id ?? 1,
            'bibliography' => $this->faker->paragraph(),
            'cover_image' => null,
            'price' => $this->faker->randomFloat(2, 5, 50),
        ];
    }
}