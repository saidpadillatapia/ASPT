<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Notification;
use App\Events\MessageSent;
use App\Events\NotificationCreated;
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

        // Crear notificación de mensaje para los otros usuarios
        $otherUsers = User::where('id', '!=', Auth::id())->get();
        foreach ($otherUsers as $otherUser) {
            $notif = Notification::create([
                'user_id' => $otherUser->id,
                'type' => 'mensaje',
                'title' => 'Nuevo mensaje de ' . Auth::user()->name,
                'description' => Auth::user()->name . ' te ha enviado un mensaje: "' . mb_substr($request->message, 0, 50) . '"',
            ]);
            broadcast(new NotificationCreated($notif));
        }

        return response()->json($msg, 201);
    }
}
