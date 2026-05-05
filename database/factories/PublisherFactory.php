<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PublisherFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),

            // opcional
            'logo' => $this->faker->optional()->imageUrl(200, 200, 'business'),

            // texto que vai ser cifrado automaticamente
            'notes' => $this->faker->paragraph(),
        ];
    }
}