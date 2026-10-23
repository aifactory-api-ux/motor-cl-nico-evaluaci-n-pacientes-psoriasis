<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Almacén de caché por defecto
    |--------------------------------------------------------------------------
    |
    | El caché de resultados del motor de elegibilidad se apoya en Amazon
    | ElastiCache for Redis, compartido por todas las tareas de ECS/Fargate.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Almacenes de caché
    |--------------------------------------------------------------------------
    |
    | "redis" es el almacén de producción, "array" se usa en las pruebas para
    | mantener el aislamiento entre casos y "file" sirve como respaldo local
    | cuando ElastiCache no está disponible en el entorno de desarrollo.
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Prefijo de las claves de caché
    |--------------------------------------------------------------------------
    |
    | Evita colisiones con otras aplicaciones que comparten la misma instancia
    | de ElastiCache.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

];
