<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $messages = Message::with('user:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $msg = Message::create([
            'user_id' => Auth::id(),
            'message' => $request->message,
        ]);

        $msg->load('user:id,name');

        broadcast(new MessageSent($msg))->toOthers();

        return response()->json($msg, 201);
    }
}
