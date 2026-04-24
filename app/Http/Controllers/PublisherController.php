<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublisherController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'logo_filter' => ['nullable', 'in:all,with_logo,without_logo'],
            'sort' => ['nullable', 'in:name,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $sort = $validated['sort'] ?? 'name';
        $direction = $validated['direction'] ?? 'asc';
        $logoFilter = $validated['logo_filter'] ?? 'all';

        $publishers = Publisher::query()
            ->withCount('books')
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($logoFilter === 'with_logo', function ($query) {
                $query->whereNotNull('logo')->where('logo', '!=', '');
            })
            ->when($logoFilter === 'without_logo', function ($query) {
                $query->where(function ($query) {
                    $query->whereNull('logo')->orWhere('logo', '');
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Publishers/Index', [
            'publishers' => $publishers,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'logo_filter' => $logoFilter,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }
}