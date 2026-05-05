<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function show(User $user)
    {
        $auth = Auth::user();

        if ($auth->isCitizen() && $auth->id !== $user->id) {
            abort(403);
        }

        $user->load([
            'requests.book'
        ]);

        return inertia('Users/Show', [
            'user' => $user
        ]);
    }

    public function index()
    {
        return inertia('Admin/Users/Index', [
            'users' => User::select('id', 'name', 'email', 'role')
                ->latest()
                ->get()
        ]);
    }

    public function create()
    {
        return inertia('Admin/Users/Create');
    }

    public function store(Request $request)
    {
        $auth = Auth::user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,citizen',
        ]);

        if ($data['role'] === 'admin' && !$auth->isAdmin()) {
            abort(403);
        }

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilizador criado com sucesso!');
    }
}