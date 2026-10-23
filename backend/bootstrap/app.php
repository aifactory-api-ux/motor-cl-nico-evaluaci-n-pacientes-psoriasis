<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Las rutas viven en routes/api.php y se publican bajo el prefijo /api/v1.
        // Se declara como arreglo para que Laravel las cargue solo cuando el archivo exista.
        api: [__DIR__.'/../routes/api.php'],
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // El servicio corre dentro de una tarea ECS/Fargate detrás del balanceador de carga,
        // por lo que las cabeceras de proxy son la única fuente confiable del esquema y la IP.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Todas las peticiones de la API responden JSON, incluso si el cliente no envía Accept.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/v1/*') || $request->expectsJson()
        );

        // Peticiones sin token Bearer válido (NFR004).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => 'No autenticado. Se requiere un token Bearer válido.',
            ], Response::HTTP_UNAUTHORIZED);
        });

        // Peticiones con un usuario autenticado pero sin permisos para la operación.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return response()->json([
                'message' => 'No autorizado para ejecutar esta operación.',
            ], Response::HTTP_FORBIDDEN);
        });

        // Los errores del motor se reportan a Amazon CloudWatch con contexto estructurado.
        $exceptions->reportable(function (Throwable $e): void {
            if (! class_exists(\App\Infrastructure\AWS\CloudWatchLogger::class)) {
                return;
            }

            \App\Infrastructure\AWS\CloudWatchLogger::write('error', $e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });
    })->create();
