<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\BookAvailabilityAlert;
use Illuminate\Support\Facades\Auth;

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
            'requests.user',
            'reviews' => function ($query) {
                $query->where('status', 'active')->with('user');
            },
        ]);

        $book->is_available = !$book->requests->where('status', 'active')->count();

        $user = Auth::user();

        $hasAvailabilityAlert = false;

        if ($user && $user->isCitizen()) {
            $hasAvailabilityAlert = BookAvailabilityAlert::where('book_id', $book->id)
                ->where('user_id', $user->id)
                ->whereNull('notified_at')
                ->exists();
        }

        return inertia('Books/Show', [
            'book' => $book,
            'relatedBooks' => $this->getRelatedBooks($book),
            'hasAvailabilityAlert' => $hasAvailabilityAlert,
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

    private function getRelatedBooks(Book $book)
    {
        $baseKeywords = $this->extractKeywords($book->bibliography ?? '');

        if (empty($baseKeywords)) {
            return collect();
        }

        return Book::query()
            ->where('id', '!=', $book->id)
            ->with(['publisher:id,name', 'authors:id,name'])
            ->get()
            ->map(function ($candidate) use ($baseKeywords) {
                $candidateKeywords = $this->extractKeywords($candidate->bibliography ?? '');

                $commonKeywords = array_values(array_intersect($baseKeywords, $candidateKeywords));

                $candidate->relation_score = count($commonKeywords);
                $candidate->common_keywords = array_slice($commonKeywords, 0, 8);

                return $candidate;
            })
            ->filter(function ($candidate) {
                return $candidate->relation_score > 0;
            })
            ->sortByDesc('relation_score')
            ->take(4)
            ->values();
    }

    private function extractKeywords(?string $text): array
    {
        if (!$text) {
            return [];
        }

        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $text = mb_strtolower($text, 'UTF-8');

        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = [
            'de', 'do', 'da', 'dos', 'das',
            'e', 'a', 'o', 'os', 'as',
            'um', 'uma', 'uns', 'umas',
            'para', 'com', 'sem', 'por',
            'no', 'na', 'nos', 'nas',
            'que', 'se', 'ao', 'aos',
            'ou', 'como', 'mais', 'menos',
            'sobre', 'este', 'esta', 'estes', 'estas',
            'isto', 'isso', 'aquele', 'aquela',
            'the', 'and', 'of', 'to', 'in',
            'for', 'with', 'on', 'by',
            'is', 'are', 'from', 'this', 'that',
        ];

        $keywords = collect($words)
            ->filter(function ($word) use ($stopWords) {
                return mb_strlen($word, 'UTF-8') >= 4
                    && !in_array($word, $stopWords);
            })
            ->unique()
            ->values()
            ->toArray();

        return $keywords;
    }
}