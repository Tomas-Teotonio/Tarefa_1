<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $authors = [
            ['name' => 'Maria Silva', 'photo' => null],
            ['name' => 'João Costa', 'photo' => null],
            ['name' => 'Rita Melo', 'photo' => null],
            ['name' => 'André Sousa', 'photo' => null],
            ['name' => 'Carla Martins', 'photo' => null],
            ['name' => 'Tiago Fernandes', 'photo' => null],
        ];

        foreach ($authors as $author) {
            Author::updateOrCreate(
                ['name' => $author['name']],
                $author
            );
        }
    }
}