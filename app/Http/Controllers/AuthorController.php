<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthorController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'photo_filter' => ['nullable', 'in:all,with_photo,without_photo'],
            'sort' => ['nullable', 'in:name,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $photoFilter = $validated['photo_filter'] ?? 'all';

        $authors = Author::query()
            ->withCount('books')
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($photoFilter === 'with_photo', function ($query) {
                $query->whereNotNull('photo')->where('photo', '!=', '');
            })
            ->when($photoFilter === 'without_photo', function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('photo')->orWhere('photo', '');
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Authors/Index', [
            'authors' => $authors,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'photo_filter' => $photoFilter,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }
}