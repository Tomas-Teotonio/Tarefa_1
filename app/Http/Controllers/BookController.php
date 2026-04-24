<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Exports\BooksExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BookController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'author_id' => ['nullable', 'integer', 'exists:authors,id'],
            'sort' => ['nullable', 'in:isbn,name,price,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';

        $books = Book::query()
            ->with(['publisher:id,name', 'authors:id,name'])
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('isbn', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('publisher', function ($publisherQuery) use ($search) {
                            $publisherQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('authors', function ($authorQuery) use ($search) {
                            $authorQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['publisher_id'] ?? null, function ($query, $publisherId) {
                $query->where('publisher_id', $publisherId);
            })
            ->when($validated['author_id'] ?? null, function ($query, $authorId) {
                $query->whereHas('authors', function ($authorQuery) use ($authorId) {
                    $authorQuery->where('authors.id', $authorId);
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Books/Index', [
            'books' => $books,
            'publishers' => Publisher::orderBy('name')->get(['id', 'name']),
            'authors' => Author::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $validated['search'] ?? '',
                'publisher_id' => $validated['publisher_id'] ?? '',
                'author_id' => $validated['author_id'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new BooksExport($request->only([
                'search',
                'publisher_id',
                'author_id',
                'sort',
                'direction',
            ])),
            'books.xlsx'
        );
    }
}