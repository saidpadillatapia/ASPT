<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * REGISTRO DE USUARIO
     * 
     * Crea un nuevo usuario en la base de datos y envía un correo de verificación.
     * El usuario NO puede usar la API hasta que verifique su correo.
     * 
     * POST /api/register
     * Body: { name, email, password, password_confirmation }
     */
    public function register(Request $request): JsonResponse
    {
        // Validar los datos de entrada
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // requiere password_confirmation
        ]);

        // Crear el usuario con rol 'user' por defecto
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Se hashea automáticamente por el cast 'hashed'
            'role' => 'user',
        ]);

        // Disparar evento Registered que envía el email de verificación
        event(new Registered($user));

        // Crear un token de Sanctum para el usuario recién registrado
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente. Revisa tu correo para verificar tu cuenta.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified' => false,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * LOGIN DE USUARIO
     * 
     * Autentica al usuario y devuelve un token Bearer de Sanctum.
     * Este token se debe enviar en el header: Authorization: Bearer {token}
     * 
     * POST /api/login
     * Body: { email, password }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Buscar el usuario por email
        $user = User::where('email', $request->email)->first();

        // Verificar credenciales
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        // Verificar que el email esté confirmado
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión.',
                'email_verified' => false,
            ], 403);
        }

        // Eliminar tokens anteriores (solo permite una sesión activa)
        $user->tokens()->delete();

        // Crear nuevo token con el rol como ability
        $token = $user->createToken('auth-token', [$user->role])->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified' => true,
            ],
            'token' => $token,
        ]);
    }

    /**
     * LOGOUT
     * 
     * Elimina el token actual del usuario, invalidando su sesión.
     * 
     * POST /api/logout
     * Header: Authorization: Bearer {token}
     */
    public function logout(Request $request): JsonResponse
    {
        // Eliminar el token que se usó para esta petición
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada exitosamente']);
    }

    /**
     * OBTENER USUARIO ACTUAL
     * 
     * Devuelve la información del usuario autenticado.
     * 
     * GET /api/user
     * Header: Authorization: Bearer {token}
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified' => $user->hasVerifiedEmail(),
            ]
        ]);
    }

    /**
     * REENVIAR EMAIL DE VERIFICACIÓN
     * 
     * Si el usuario no recibió el correo, puede solicitar que se reenvíe.
     * 
     * POST /api/email/resend
     * Header: Authorization: Bearer {token}
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'El correo ya está verificado'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Correo de verificación reenviado']);
    }

    /**
     * VERIFICAR EMAIL
     * 
     * Cuando el usuario hace clic en el link del correo, llega aquí.
     * Marca el email como verificado.
     * 
     * GET /api/email/verify/{id}/{hash}
     */
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        // Verificar que el hash coincida con el email del usuario
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'Link de verificación inválido'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'El correo ya estaba verificado']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Correo verificado exitosamente. Ya puedes iniciar sesión.']);
    }
}
