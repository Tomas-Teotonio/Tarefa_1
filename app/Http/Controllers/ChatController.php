<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $rooms = ChatRoom::query()
            ->with(['users:id,name,email,profile_photo_path'])
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            })
            ->latest()
            ->get();

        $directUsers = User::query()
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'profile_photo_path']);

        return Inertia::render('Chat/Index', [
            'rooms' => $rooms,
            'directUsers' => $directUsers,
        ]);
    }

    public function room(ChatRoom $room)
    {
        $user = Auth::user();

        $this->authorizeRoomAccess($room, $user);

        $room->load([
            'users:id,name,email,role,status,profile_photo_path',
            'messages.sender:id,name,email,profile_photo_path',
        ]);

        $messages = $room->messages()
            ->with('sender:id,name,email,profile_photo_path')
            ->oldest()
            ->get();

        return Inertia::render('Chat/Room', [
            'room' => $room,
            'messages' => $messages,
        ]);
    }

    public function storeRoomMessage(Request $request, ChatRoom $room)
    {
        $user = Auth::user();

        $this->authorizeRoomAccess($room, $user);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'receiver_id' => null,
            'body' => $data['body'],
        ]);

        $message->load('sender:id,name,email,profile_photo_path');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
            ], 201);
        }

        return back()->with('success', 'Mensagem enviada.');
    }

    public function roomMessages(ChatRoom $room)
    {
        $user = Auth::user();

        $this->authorizeRoomAccess($room, $user);

        $messages = $room->messages()
            ->with('sender:id,name,email,profile_photo_path')
            ->oldest()
            ->get();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function direct(User $user)
    {
        $auth = Auth::user();

        if ($auth->id === $user->id) {
            abort(403);
        }

        if ($user->status !== 'active') {
            abort(403);
        }

        $messages = ChatMessage::query()
            ->with([
                'sender:id,name,email,profile_photo_path',
                'receiver:id,name,email,profile_photo_path',
            ])
            ->whereNull('room_id')
            ->where(function ($query) use ($auth, $user) {
                $query->where(function ($q) use ($auth, $user) {
                    $q->where('sender_id', $auth->id)
                        ->where('receiver_id', $user->id);
                })->orWhere(function ($q) use ($auth, $user) {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $auth->id);
                });
            })
            ->oldest()
            ->get();

        ChatMessage::query()
            ->whereNull('room_id')
            ->where('sender_id', $user->id)
            ->where('receiver_id', $auth->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return Inertia::render('Chat/Direct', [
            'chatUser' => $user,
            'messages' => $messages,
        ]);
    }

    public function storeDirectMessage(Request $request, User $user)
    {
        $auth = Auth::user();

        if ($auth->id === $user->id) {
            abort(403);
        }

        if ($user->status !== 'active') {
            abort(403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = ChatMessage::create([
            'room_id' => null,
            'sender_id' => $auth->id,
            'receiver_id' => $user->id,
            'body' => $data['body'],
        ]);

        $message->load([
            'sender:id,name,email,profile_photo_path',
            'receiver:id,name,email,profile_photo_path',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
            ], 201);
        }

        return back()->with('success', 'Mensagem enviada.');
    }

    public function directMessages(User $user)
    {
        $auth = Auth::user();

        if ($auth->id === $user->id) {
            abort(403);
        }

        if ($user->status !== 'active') {
            abort(403);
        }

        $messages = ChatMessage::query()
            ->with([
                'sender:id,name,email,profile_photo_path',
                'receiver:id,name,email,profile_photo_path',
            ])
            ->whereNull('room_id')
            ->where(function ($query) use ($auth, $user) {
                $query->where(function ($q) use ($auth, $user) {
                    $q->where('sender_id', $auth->id)
                        ->where('receiver_id', $user->id);
                })->orWhere(function ($q) use ($auth, $user) {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $auth->id);
                });
            })
            ->oldest()
            ->get();

        ChatMessage::query()
            ->whereNull('room_id')
            ->where('sender_id', $user->id)
            ->where('receiver_id', $auth->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function createRoom()
    {
        $users = User::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'profile_photo_path']);

        return Inertia::render('Chat/CreateRoom', [
            'users' => $users,
        ]);
    }

    public function storeRoom(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255', 'unique:chat_rooms,reference'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['exists:users,id'],
        ]);

        $avatarPath = null;

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('chat-rooms', 'public');
        }

        $room = ChatRoom::create([
            'avatar' => $avatarPath,
            'name' => $data['name'],
            'reference' => $data['reference'] ?: Str::slug($data['name']) . '-' . uniqid(),
            'created_by' => Auth::id(),
        ]);

        $users = collect($data['users'])
            ->push(Auth::id())
            ->unique()
            ->values();

        foreach ($users as $userId) {
            $room->users()->syncWithoutDetaching([
                $userId => [
                    'invited_by' => Auth::id(),
                ],
            ]);
        }

        return redirect()
            ->route('chat.room', $room)
            ->with('success', 'Sala criada com sucesso.');
    }

    private function authorizeRoomAccess(ChatRoom $room, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $hasAccess = $room->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$hasAccess) {
            abort(403);
        }
    }
}