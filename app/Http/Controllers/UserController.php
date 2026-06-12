<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CRUD de usuarios - Solo para administradores
 */
class UserController extends Controller
{
    /**
     * Listar todos los usuarios (con su estado activo/inactivo)
     * GET /api/users
     */
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'email_verified_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * Actualizar un usuario
     * PUT /api/users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $id,
            'role' => 'sometimes|in:admin,user',
        ]);

        // Solo actualizar los campos que se enviaron
        if ($request->has('name')) $user->name = $request->name;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('role')) $user->role = $request->role;

        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado',
            'user' => $user,
        ]);
    }

    /**
     * Eliminar un usuario
     * DELETE /api/users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // No permitir eliminarse a sí mismo
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'No puedes eliminarte a ti mismo'], 403);
        }

        // Eliminar sus tokens primero
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}
