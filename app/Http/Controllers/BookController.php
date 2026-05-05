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
            ->with([
                'publisher:id,name',
                'authors:id,name',
                'requests' => function ($q) {
                    $q->where('status', 'active');
                }
            ])
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
            ->through(function ($book) {
                $book->is_available = $book->requests->isEmpty();
                return $book;
            })
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

    public function show(Book $book)
    {
        $book->load([
            'publisher',
            'authors',
            'requests.user'
        ]);

        $book->is_available = !$book->requests->where('status', 'active')->count();

        return inertia('Books/Show', [
            'book' => $book
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'isbn' => 'required',
            'name' => 'required',
            'publisher_id' => 'required|exists:publishers,id',
            'price' => 'required|numeric',
            'cover_image' => 'nullable|image|max:2048',
            'authors' => 'array', 
            'authors.*' => 'exists:authors,id',
        ]);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')
                ->store('books', 'public');
        }

        $book = Book::create($data);

        $book->authors()->sync($data['authors'] ?? []);

        return redirect()->route('admin.books.index');
    }

    public function update(Request $request, Book $book)
{
    $data = $request->validate([
        'isbn' => 'required',
        'name' => 'required',
        'publisher_id' => 'required|exists:publishers,id',
        'price' => 'required|numeric',
        'cover_image' => 'nullable|image|max:2048',
        'authors' => 'array',
        'authors.*' => 'exists:authors,id',
    ]);

    if ($request->hasFile('cover_image')) {
        $data['cover_image'] = $request->file('cover_image')
            ->store('books', 'public');
    } else {
        unset($data['cover_image']);
    }

    $book->update($data);

    $book->authors()->sync($data['authors'] ?? []);

    return redirect()->route('admin.books.index');
}

    public function create()
    {
        return inertia('Books/Create', [
            'publishers' => Publisher::select('id', 'name')->get(),
            'authors' => Author::select('id', 'name')->get(), 
        ]);
    }

    public function edit(Book $book)
    {
        $book->load('authors'); 

        return inertia('Books/Edit', [
            'book' => $book,
            'publishers' => Publisher::select('id', 'name')->get(),
            'authors' => Author::select('id', 'name')->get(),
        ]);
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', 'Livro apagado com sucesso!');
    }

    public function adminIndex()
    {
        $books = Book::with(['publisher', 'authors'])
            ->latest()
            ->get();

        return inertia('Admin/Books/Index', [
            'books' => $books
        ]);
    }
}