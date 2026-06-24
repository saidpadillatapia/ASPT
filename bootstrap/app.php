<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // statefulApi() permite que Sanctum autentique peticiones del SPA usando cookies de sesión
        $middleware->statefulApi();

        // CORS para permitir peticiones desde el frontend
        $middleware->append(HandleCors::class);

        // Desactivar CSRF para todas las rutas (porque usamos tokens de Sanctum)
        $middleware->validateCsrfTokens(except: [
            '*',
        ]);

        // Registrar alias de middleware personalizados
        $middleware->alias([
            'role' => CheckRole::class, // Middleware para verificar roles
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Cuando alguien intenta acceder sin autenticarse, responder con JSON 401
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json(['message' => 'No autenticado'], 401);
        });

        // Cuando una ruta no existe, responder con JSON 404
        $exceptions->render(function (\Symfony\Component\Routing\Exception\RouteNotFoundException $e, $request) {
            return response()->json(['message' => 'No autenticado'], 401);
        });
    })->create();
