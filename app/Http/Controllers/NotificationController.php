<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Events\NotificationCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Listar notificaciones del usuario
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->take(50)
            ->get();

        return response()->json($notifications);
    }

    // Contar no leídas
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // Marcar como leída
    public function markAsRead(int $id): JsonResponse
    {
        $notif = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $notif->update(['read' => true]);

        return response()->json(['success' => true]);
    }

    // Crear notificación (para pruebas)
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:mensaje,multa,asamblea,pago_atrasado',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
        ]);

        $notif = Notification::create([
            'user_id' => $request->user_id,
            'type' => $request->type,
            'title' => $request->title,
            'description' => $request->description ?? '',
        ]);

        broadcast(new NotificationCreated($notif));

        return response()->json($notif, 201);
    }
}
