<?php

namespace App\Http\Controllers;

use App\Mail\BookAvailableMail;
use App\Mail\RequestCreatedMail;
use App\Models\Book;
use App\Models\BookAvailabilityAlert;
use App\Models\Request as BookRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class BookRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $query = BookRequest::with(['book', 'user']);

        if ($user->isCitizen()) {
            $query->where('user_id', $user->id);
        }

        $requests = $query->latest()->get()->map(function ($req) {
            $req->user_photo_url = $req->user_photo
                ? asset('storage/' . $req->user_photo)
                : null;

            return $req;
        });

        $active = BookRequest::where('status', 'active')->count();

        $last30days = BookRequest::where('created_at', '>=', now()->subDays(30))->count();

        $returnedToday = BookRequest::whereDate('actual_return_date', now())->count();

        return inertia('Requests/Index', [
            'requests' => $requests,
            'stats' => [
                'active' => $active,
                'last30days' => $last30days,
                'returnedToday' => $returnedToday,
            ]
        ]);
    }

    public function store(Book $book)
    {
        $user = Auth::user();

        if (!$book->isAvailable()) {
            return back()->with('error', 'Livro indisponível.');
        }

        $alreadyRequested = BookRequest::where('book_id', $book->id)
            ->where('status', 'active')
            ->exists();

        if ($alreadyRequested) {
            return back()->with('error', 'Este livro já está requisitado.');
        }

        $activeRequests = BookRequest::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        if ($user->isCitizen() && $activeRequests >= 3) {
            return back()->with('error', 'Máximo de 3 livros ativos.');
        }

        $request = BookRequest::create([
            'number' => 'TEMP',
            'user_id' => $user->id,
            'book_id' => $book->id,
            'request_date' => now(),
            'expected_return_date' => now()->addDays(5),
            'status' => 'active',
        ]);

        $request->number = 'REQ-' . str_pad($request->id, 5, '0', STR_PAD_LEFT);
        $request->save();

        $request->load(['user', 'book']);

        Mail::to($user->email)->send(new RequestCreatedMail($request));

        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new RequestCreatedMail($request));
        }

        return back()->with('success', 'Livro requisitado com sucesso!');
    }

    public function return(Request $httpRequest, BookRequest $request)
    {
        $httpRequest->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $now = now();

        $days = $request->request_date
            ? $request->request_date->diffInDays($now)
            : 0;

        $days = floor($days);

        $path = $httpRequest->file('photo')->store('returns', 'public');

        $request->update([
            'actual_return_date' => $now,
            'days_used' => $days,
            'status' => 'returned',
            'user_photo' => $path,
        ]);

        $this->notifyAvailabilityAlerts($request->book_id);

        return back()->with('success', 'Livro devolvido com sucesso!');
    }

    public function show(BookRequest $loanRequest)
    {
        $user = Auth::user();

        if ($user->isCitizen() && $loanRequest->user_id !== $user->id) {
            abort(403);
        }

        $loanRequest->load([
            'book.publisher',
            'book.authors',
            'user',
            'review',
        ]);

        return inertia('Requests/Show', [
            'request' => $loanRequest,
        ]);
    }

    private function notifyAvailabilityAlerts(int $bookId): void
    {
        $alerts = BookAvailabilityAlert::with(['book', 'user'])
            ->where('book_id', $bookId)
            ->whereNull('notified_at')
            ->get();

        foreach ($alerts as $alert) {
            Mail::to($alert->user->email)->send(new BookAvailableMail($alert));

            $alert->update([
                'notified_at' => now(),
            ]);
        }
    }
}