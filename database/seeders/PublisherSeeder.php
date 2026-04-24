<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    public function run(): void
    {
        $publishers = [
            [
                'name' => 'Inovcorp Press',
                'logo' => null,
                'notes' => 'Editora focada em tecnologia, software e inovação.',
            ],
            [
                'name' => 'Future Books',
                'logo' => null,
                'notes' => 'Publicações modernas sobre frontend, UX e desenvolvimento web.',
            ],
            [
                'name' => 'Byte Editora',
                'logo' => null,
                'notes' => 'Catálogo orientado para segurança, dados e backend.',
            ],
            [
                'name' => 'Dev House',
                'logo' => null,
                'notes' => 'Livros técnicos para programadores e equipas de produto.',
            ],
        ];

        foreach ($publishers as $publisher) {
            Publisher::updateOrCreate(
                ['name' => $publisher['name']],
                $publisher
            );
        }
    }
}