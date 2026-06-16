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
     * Cada dispositivo obtiene su propio token (sesión múltiple).
     * El nombre del token identifica el dispositivo/navegador.
     * 
     * POST /api/login
     * Body: { email, password, device_name? }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
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

        // Crear token por dispositivo (NO elimina tokens anteriores)
        // Así el usuario puede tener sesión en múltiples dispositivos
        $deviceName = $request->device_name ?? $this->getDeviceName($request);
        $token = $user->createToken($deviceName, [$user->role])->plainTextToken;

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
     * Genera un nombre de dispositivo basado en el User-Agent
     */
    private function getDeviceName(Request $request): string
    {
        $userAgent = $request->header('User-Agent', 'unknown');

        if (str_contains($userAgent, 'Mobile')) return 'mobile-browser';
        if (str_contains($userAgent, 'Chrome')) return 'chrome';
        if (str_contains($userAgent, 'Firefox')) return 'firefox';
        if (str_contains($userAgent, 'Safari')) return 'safari';

        return 'web-browser';
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

    /**
     * CAMBIAR CONTRASEÑA
     * 
     * Cambia la contraseña del usuario y CIERRA SESIÓN EN TODOS LOS DISPOSITIVOS
     * (elimina todos los tokens del usuario). El usuario deberá volver a iniciar sesión.
     * 
     * POST /api/change-password
     * Header: Authorization: Bearer {token}
     * Body: { current_password, new_password, new_password_confirmation }
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed', // requiere new_password_confirmation
        ]);

        $user = $request->user();

        // Verificar que la contraseña actual sea correcta
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta'], 422);
        }

        // Actualizar la contraseña
        $user->password = $request->new_password; // Se hashea automáticamente por el cast
        $user->save();

        // CERRAR SESIÓN EN TODOS LOS DISPOSITIVOS (eliminar TODOS los tokens)
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente. Se cerró sesión en todos los dispositivos. Inicia sesión nuevamente.',
        ]);
    }
}
