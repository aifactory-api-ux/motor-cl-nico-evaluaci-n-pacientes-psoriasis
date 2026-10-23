<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Controlador de sesión
    |--------------------------------------------------------------------------
    |
    | La API es stateless y se autentica con tokens Bearer, pero el estado de
    | sesión se comparte en Amazon ElastiCache for Redis para que todas las
    | tareas de ECS/Fargate mantengan el mismo contexto entre peticiones.
    |
    */

    'driver' => env('SESSION_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Duración de la sesión
    |--------------------------------------------------------------------------
    |
    | El tiempo de vida se cuenta en minutos y se aplica cuando la sesión
    | queda inactiva.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => (bool) env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => (bool) env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Conexión y almacén de Redis
    |--------------------------------------------------------------------------
    |
    | La sesión usa la conexión "default" de Redis y el almacén "redis" del
    | caché definido en config/cache.php.
    |
    */

    'connection' => env('SESSION_CONNECTION', 'default'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE', 'redis'),

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Cookie de sesión
    |--------------------------------------------------------------------------
    |
    | La cookie solo se transmite por HTTPS en producción y permanece
    | inaccesible para el JavaScript de la interfaz.
    |
    */

    'cookie' => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'laravel'), '_').'_session'),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    'secure' => (bool) env('SESSION_SECURE_COOKIE', env('APP_ENV', 'production') === 'production'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
