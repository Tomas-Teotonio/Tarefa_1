<?php

namespace App\Exports;

use App\Models\Book;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BooksExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection(): Collection
    {
        return Book::query()
            ->with(['publisher:id,name', 'authors:id,name'])
            ->when($this->filters['search'] ?? null, function ($query, $search) {
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
            ->when($this->filters['publisher_id'] ?? null, function ($query, $publisherId) {
                $query->where('publisher_id', $publisherId);
            })
            ->when($this->filters['author_id'] ?? null, function ($query, $authorId) {
                $query->whereHas('authors', function ($authorQuery) use ($authorId) {
                    $authorQuery->where('authors.id', $authorId);
                });
            })
            ->orderBy(
                $this->filters['sort'] ?? 'name',
                $this->filters['direction'] ?? 'asc'
            )
            ->get();
    }

    public function headings(): array
    {
        return [
            'ISBN',
            'Nome',
            'Editora',
            'Autores',
            'Bibliografia',
            'Imagem da Capa',
            'Preço',
        ];
    }

    public function map($book): array
    {
        return [
            $book->isbn,
            $book->name,
            $book->publisher?->name,
            $book->authors->pluck('name')->implode(', '),
            $book->bibliography,
            $book->cover_image ? url('/storage/' . $book->cover_image) : '',
            number_format((float) $book->price, 2, '.', ''),
        ];
    }
}