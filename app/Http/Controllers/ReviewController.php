<?php

namespace App\Http\Controllers;

use App\Mail\ReviewCreatedAdminMail;
use App\Mail\ReviewStatusChangedMail;
use App\Models\Request as LoanRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function store(HttpRequest $request, LoanRequest $loanRequest)
    {
        $user = Auth::user();

        if (!$user->isCitizen()) {
            abort(403);
        }

        if ($loanRequest->user_id !== $user->id) {
            abort(403);
        }

        if ($loanRequest->status !== 'returned') {
            return back()->with('error', 'Só podes avaliar livros já devolvidos.');
        }

        if ($loanRequest->review()->exists()) {
            return back()->with('error', 'Esta requisição já tem review.');
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'min:3', 'max:3000'],
        ]);

        $review = Review::create([
            'request_id' => $loanRequest->id,
            'book_id' => $loanRequest->book_id,
            'user_id' => $user->id,
            'content' => $data['content'],
            'status' => 'suspended',
        ]);

        $review->load(['user', 'book', 'request']);

        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new ReviewCreatedAdminMail($review));
        }

        return back()->with('success', 'Review enviada e aguarda aprovação.');
    }

    public function index()
    {
        $reviews = Review::with(['user', 'book'])
            ->latest()
            ->get();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function show(Review $review)
    {
        $review->load(['user', 'book', 'request', 'reviewer']);

        return Inertia::render('Admin/Reviews/Show', [
            'review' => $review,
        ]);
    }

    public function updateStatus(HttpRequest $request, Review $review)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,refused'],
            'refusal_reason' => ['nullable', 'string', 'max:3000', 'required_if:status,refused'],
        ]);

        $review->update([
            'status' => $data['status'],
            'refusal_reason' => $data['status'] === 'refused'
                ? $data['refusal_reason']
                : null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $review->load(['user', 'book']);

        Mail::to($review->user->email)->send(new ReviewStatusChangedMail($review));

        return redirect()
            ->route('admin.reviews.show', $review)
            ->with('success', 'Estado da review atualizado.');
    }
}