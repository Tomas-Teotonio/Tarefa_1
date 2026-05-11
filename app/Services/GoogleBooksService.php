<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    public function search(string $query, int $page = 1, int $maxResults = 9): array
    {
        $page = max($page, 1);
        $maxResults = min(max($maxResults, 1), 40);

        $startIndex = ($page - 1) * $maxResults;

        $response = Http::get(
            'https://www.googleapis.com/books/v1/volumes',
            [
                'q' => $query,
                'startIndex' => $startIndex,
                'maxResults' => $maxResults,
            ]
        );

        if (!$response->successful()) {
            return [
                'items' => [],
                'totalItems' => 0,
                'page' => $page,
                'maxResults' => $maxResults,
                'hasNextPage' => false,
                'hasPreviousPage' => $page > 1,
            ];
        }

        $json = $response->json();

        $items = collect($json['items'] ?? [])
            ->map(function ($item) {
                $info = $item['volumeInfo'] ?? [];

                $isbn = $this->extractIsbn($info['industryIdentifiers'] ?? []);

                return [
                    'google_id' => $item['id'] ?? null,
                    'isbn' => $isbn,
                    'title' => $info['title'] ?? 'Sem título',
                    'authors' => $info['authors'] ?? [],
                    'publisher' => $info['publisher'] ?? 'Desconhecido',
                    'published_date' => $info['publishedDate'] ?? null,
                    'description' => $info['description'] ?? null,
                    'thumbnail' => $info['imageLinks']['thumbnail'] ?? null,

                    'exists' => $isbn
                        ? Book::where('isbn', $isbn)->exists()
                        : false,
                ];
            })
            ->filter(fn ($book) => !empty($book['google_id']))
            ->values();

        $totalItems = (int) ($json['totalItems'] ?? 0);

        return [
            'items' => $items,
            'totalItems' => $totalItems,
            'page' => $page,
            'maxResults' => $maxResults,
            'hasNextPage' => ($startIndex + $maxResults) < $totalItems,
            'hasPreviousPage' => $page > 1,
        ];
    }

    private function extractIsbn(array $identifiers): ?string
    {
        $isbn13 = collect($identifiers)->firstWhere('type', 'ISBN_13');

        if ($isbn13 && isset($isbn13['identifier'])) {
            return $isbn13['identifier'];
        }

        $isbn10 = collect($identifiers)->firstWhere('type', 'ISBN_10');

        if ($isbn10 && isset($isbn10['identifier'])) {
            return $isbn10['identifier'];
        }

        return null;
    }
}