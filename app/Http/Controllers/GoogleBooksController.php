<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use App\Services\GoogleBooksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GoogleBooksController extends Controller
{
    protected GoogleBooksService $service;

    public function __construct(GoogleBooksService $service)
    {
        $this->service = $service;
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $books = $this->service->search(
            query: $request->input('q'),
            page: (int) $request->input('page', 1),
            maxResults: 9
        );

        return response()->json($books);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'google_id' => ['required', 'string'],
            'isbn' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'authors' => ['nullable', 'array'],
            'authors.*' => ['string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string'],
        ]);

        $exists = Book::where('isbn', $data['isbn'])->exists();

        if ($exists) {
            return back()->with('error', 'Este livro já existe na base de dados!');
        }

        $publisher = Publisher::firstOrCreate([
            'name' => $data['publisher'] ?: 'Desconhecido',
        ]);

        $imagePath = null;

        if (!empty($data['thumbnail'])) {
            $imagePath = $this->storeCoverImage($data['thumbnail']);
        }

        $book = Book::create([
            'isbn' => $data['isbn'],
            'name' => $data['title'],
            'publisher_id' => $publisher->id,
            'bibliography' => $data['description'] ?? null,
            'cover_image' => $imagePath,
            'price' => 0,
        ]);

        foreach ($data['authors'] ?? [] as $authorName) {
            $author = Author::firstOrCreate([
                'name' => $authorName,
            ]);

            $book->authors()->syncWithoutDetaching($author->id);
        }

        return back()->with('success', 'Livro importado com sucesso!');
    }

    private function storeCoverImage(string $url): ?string
    {
        try {
            $response = Http::get($url);

            if (!$response->successful()) {
                return null;
            }

            $fileName = 'books/' . uniqid('google_', true) . '.jpg';

            Storage::disk('public')->put($fileName, $response->body());

            return $fileName;
        } catch (\Throwable $e) {
            return null;
        }
    }
}