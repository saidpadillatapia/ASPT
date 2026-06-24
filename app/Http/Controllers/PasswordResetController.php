<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    /**
     * PASO 1: Enviar código de 6 dígitos al correo
     * 
     * POST /api/password/send-code
     * Body: { email }
     */
    public function sendCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Verificar que el usuario existe
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'No existe una cuenta con ese correo'], 404);
        }

        // Generar código aleatorio de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Eliminar códigos anteriores del mismo email
        DB::table('password_reset_codes')->where('email', $request->email)->delete();

        // Guardar el nuevo código (expira en 10 minutos)
        DB::table('password_reset_codes')->insert([
            'email' => $request->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        // Enviar el código por correo
        Mail::to($request->email)->send(new PasswordResetCode($code, $user->name));

        return response()->json([
            'message' => 'Código enviado a tu correo electrónico',
        ]);
    }

    /**
     * PASO 2: Verificar el código
     * 
     * POST /api/password/verify-code
     * Body: { email, code }
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        // Buscar el código en la base de datos
        $record = DB::table('password_reset_codes')
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Código incorrecto'], 422);
        }

        // Verificar que no haya expirado
        if (now()->greaterThan($record->expires_at)) {
            // Eliminar el código expirado
            DB::table('password_reset_codes')->where('email', $request->email)->delete();
            return response()->json(['message' => 'El código ha expirado. Solicita uno nuevo.'], 422);
        }

        return response()->json(['message' => 'Código verificado correctamente']);
    }

    /**
     * PASO 3: Restablecer la contraseña
     * 
     * POST /api/password/reset
     * Body: { email, code, password, password_confirmation }
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verificar el código una vez más
        $record = DB::table('password_reset_codes')
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Código incorrecto'], 422);
        }

        if (now()->greaterThan($record->expires_at)) {
            DB::table('password_reset_codes')->where('email', $request->email)->delete();
            return response()->json(['message' => 'El código ha expirado'], 422);
        }

        // Buscar el usuario y cambiar su contraseña
        $user = User::where('email', $request->email)->first();
        $user->password = $request->password; // Se hashea automáticamente
        $user->save();

        // Cerrar sesión en todos los dispositivos (eliminar todos los tokens)
        $user->tokens()->delete();

        // Eliminar el código usado
        DB::table('password_reset_codes')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Contraseña restablecida exitosamente. Inicia sesión con tu nueva contraseña.',
        ]);
    }
}
