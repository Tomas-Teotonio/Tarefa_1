<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'isbn' => '9789890000011',
                'name' => 'Arquitetura Laravel',
                'publisher' => 'Inovcorp Press',
                'authors' => ['Maria Silva'],
                'bibliography' => 'Livro sobre organização de projetos Laravel, boas práticas, arquitetura limpa e manutenção.',
                'cover_image' => null,
                'price' => 29.90,
            ],
            [
                'isbn' => '9789890000028',
                'name' => 'Vue 3 Moderno',
                'publisher' => 'Future Books',
                'authors' => ['João Costa', 'Rita Melo'],
                'bibliography' => 'Aborda composição, componentes, estados, routing e integração com aplicações modernas.',
                'cover_image' => null,
                'price' => 34.50,
            ],
            [
                'isbn' => '9789890000035',
                'name' => 'Segurança Web Prática',
                'publisher' => 'Byte Editora',
                'authors' => ['André Sousa'],
                'bibliography' => 'Foco em autenticação, autorização, hardening, gestão de sessões e proteção de dados.',
                'cover_image' => null,
                'price' => 24.90,
            ],
            [
                'isbn' => '9789890000042',
                'name' => 'SQLite para Aplicações Reais',
                'publisher' => 'Dev House',
                'authors' => ['Carla Martins'],
                'bibliography' => 'Introdução prática ao SQLite em aplicações pequenas e médias com foco em produtividade.',
                'cover_image' => null,
                'price' => 21.00,
            ],
            [
                'isbn' => '9789890000059',
                'name' => 'UI com Tailwind e daisyUI',
                'publisher' => 'Future Books',
                'authors' => ['Rita Melo', 'Tiago Fernandes'],
                'bibliography' => 'Criação de interfaces modernas com utilitários, componentes e sistemas de tema.',
                'cover_image' => null,
                'price' => 27.75,
            ],
            [
                'isbn' => '9789890000066',
                'name' => 'Backend Profissional em Laravel',
                'publisher' => 'Inovcorp Press',
                'authors' => ['Maria Silva', 'André Sousa'],
                'bibliography' => 'Controllers, services, validação, relações, eventos, filas e organização profissional de backend.',
                'cover_image' => null,
                'price' => 39.90,
            ],
        ];

        foreach ($books as $bookData) {
            $publisher = Publisher::where('name', $bookData['publisher'])->firstOrFail();

            $book = Book::updateOrCreate(
                ['isbn' => $bookData['isbn']],
                [
                    'name' => $bookData['name'],
                    'publisher_id' => $publisher->id,
                    'bibliography' => $bookData['bibliography'],
                    'cover_image' => $bookData['cover_image'],
                    'price' => $bookData['price'],
                ]
            );

            $authorIds = Author::whereIn('name', $bookData['authors'])->pluck('id')->toArray();

            $book->authors()->sync($authorIds);
        }
    }
}