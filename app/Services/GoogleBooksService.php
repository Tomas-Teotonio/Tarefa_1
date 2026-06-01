<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleBooksService
{
    public function search(string $query, int $page = 1, int $maxResults = 9): array
    {
        $query = trim($query);

        $page = max($page, 1);
        $maxResults = min(max($maxResults, 1), 40);

        if ($query === '') {
            return $this->emptyResponse($page, $maxResults);
        }

        $startIndex = ($page - 1) * $maxResults;

        $cacheKey = 'google_books_' . md5(
            mb_strtolower($query) . "_{$page}_{$maxResults}"
        );

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $params = [
                'q' => $query,
                'startIndex' => $startIndex,
                'maxResults' => $maxResults,
                'printType' => 'books',
            ];

            if (config('services.google_books.key')) {
                $params['key'] = config('services.google_books.key');
            }

            $response = Http::timeout(15)
                ->retry(2, 500)
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => config('app.name') . '/1.0',
                ])
                ->get('https://www.googleapis.com/books/v1/volumes', $params);

            if ($response->status() === 429) {
                return array_merge(
                    $this->emptyResponse($page, $maxResults),
                    [
                        'error' => 'A Google Books API recebeu demasiados pedidos. Aguarda um pouco e tenta novamente.',
                        'rateLimited' => true,
                    ]
                );
            }

            if (!$response->successful()) {
                Log::warning('Google Books API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return array_merge(
                    $this->emptyResponse($page, $maxResults),
                    [
                        'error' => 'Google Books respondeu com o estado ' . $response->status(),
                    ]
                );
            }

            $json = $response->json();

            $items = collect($json['items'] ?? [])
                ->map(function ($item) {
                    $info = $item['volumeInfo'] ?? [];

                    $googleId = $item['id'] ?? null;
                    $isbn = $this->extractIsbn($info['industryIdentifiers'] ?? []);

                    return [
                        'google_id' => $googleId,
                        'isbn' => $isbn,
                        'title' => $info['title'] ?? 'Sem título',
                        'authors' => $info['authors'] ?? [],
                        'publisher' => $info['publisher'] ?? 'Desconhecido',
                        'published_date' => $info['publishedDate'] ?? null,
                        'description' => $info['description'] ?? null,
                        'thumbnail' => $info['imageLinks']['thumbnail']
                            ?? $info['imageLinks']['smallThumbnail']
                            ?? null,

                        'exists' => $isbn
                            ? Book::where('isbn', $isbn)->exists()
                            : false,
                    ];
                })
                ->filter(fn ($book) => !empty($book['google_id']))
                ->values()
                ->toArray();

            $totalItems = (int) ($json['totalItems'] ?? 0);

            $result = [
                'items' => $items,
                'totalItems' => $totalItems,
                'page' => $page,
                'maxResults' => $maxResults,
                'hasNextPage' => ($startIndex + $maxResults) < $totalItems,
                'hasPreviousPage' => $page > 1,
                'error' => null,
                'rateLimited' => false,
            ];

            Cache::put($cacheKey, $result, now()->addHour());

            return $result;

        } catch (\Throwable $e) {
            Log::error('Google Books API exception', [
                'message' => $e->getMessage(),
            ]);

            return array_merge(
                $this->emptyResponse($page, $maxResults),
                [
                    'error' => 'Erro ao contactar a Google Books API: ' . $e->getMessage(),
                ]
            );
        }
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

    private function emptyResponse(int $page, int $maxResults): array
    {
        return [
            'items' => [],
            'totalItems' => 0,
            'page' => $page,
            'maxResults' => $maxResults,
            'hasNextPage' => false,
            'hasPreviousPage' => $page > 1,
            'error' => null,
            'rateLimited' => false,
        ];
    }
}