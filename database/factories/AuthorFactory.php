<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),

            
            'photo' => $this->faker->optional()->imageUrl(200, 200, 'people'),
        ];
    }
}