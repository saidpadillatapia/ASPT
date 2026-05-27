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
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return response()->json($notifications);
    }

    // Ver detalle de una notificación (y marcarla como leída)
    public function show(int $id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        // Marcar como leída al ver el detalle
        if (!$notification->read) {
            $notification->update(['read' => true]);
        }

        return response()->json($notification);
    }

    // Marcar como leída
    public function markAsRead(int $id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->findOrFail($id);

        $notification->update(['read' => true]);

        return response()->json(['message' => 'Marcada como leída']);
    }

    // Marcar todas como leídas
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['message' => 'Todas marcadas como leídas']);
    }

    // Contar no leídas
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    // Crear notificación de prueba (para demostrar el WebSocket)
    public function createTest(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:mensaje,multa,asamblea,pago_atrasado',
        ]);

        $titles = [
            'mensaje' => 'Nuevo mensaje recibido',
            'multa' => 'Multa registrada',
            'asamblea' => 'Asamblea programada',
            'pago_atrasado' => 'Pago atrasado detectado',
        ];

        $descriptions = [
            'mensaje' => 'Tienes un nuevo mensaje en el chat general.',
            'multa' => 'Se ha registrado una multa de $500 por incumplimiento del reglamento.',
            'asamblea' => 'Se ha programado una asamblea extraordinaria para el próximo viernes a las 18:00.',
            'pago_atrasado' => 'Tu cuota de mantenimiento del mes de mayo está pendiente de pago.',
        ];

        $type = $request->type;

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'type' => $type,
            'title' => $titles[$type],
            'description' => $descriptions[$type],
        ]);

        broadcast(new NotificationCreated($notification));

        return response()->json($notification, 201);
    }
}
