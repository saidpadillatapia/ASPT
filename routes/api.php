<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (no requieren autenticación)
|--------------------------------------------------------------------------
*/

// Registro de usuario - crea cuenta y envía correo de verificación
Route::post('/register', [AuthController::class, 'register']);

// Login - devuelve un token Bearer de Sanctum
Route::post('/login', [AuthController::class, 'login']);

// Verificación de email - el usuario hace clic en el link del correo
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (requieren token: Authorization: Bearer {token})
|--------------------------------------------------------------------------
| auth:sanctum = verifica que el usuario tenga un token válido
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- AUTH ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // --- CHAT (todos los usuarios autenticados) ---
    Route::get('/messages', [ChatController::class, 'index']);
    Route::post('/messages', [ChatController::class, 'store']);

    // --- NOTIFICACIONES (todos los usuarios autenticados) ---
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // --- RUTAS SOLO PARA ADMIN ---
    Route::middleware('role:admin')->group(function () {
        // Crear notificaciones (solo admin puede enviar notificaciones a otros)
        Route::post('/notifications', [NotificationController::class, 'store']);

        // CRUD de usuarios
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
});
