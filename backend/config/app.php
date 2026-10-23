<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nombre de la aplicación
    |--------------------------------------------------------------------------
    |
    | Identifica al MVP del motor de pre-selección clínica en las notificaciones,
    | en los logs estructurados de CloudWatch y en las cabeceras de la API.
    |
    */

    'name' => env('APP_NAME', 'Motor de Pre-selección Clínica Etapa PO'),

    /*
    |--------------------------------------------------------------------------
    | Entorno de ejecución
    |--------------------------------------------------------------------------
    |
    | En producción el valor es "production" y el modo debug permanece desactivado
    | para no exponer información sensible de los pacientes evaluados.
    |
    */

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL pública del backend
    |--------------------------------------------------------------------------
    |
    | En local el servicio escucha en el puerto 23080; en AWS corresponde al
    | balanceador de carga de ECS/Fargate detrás de CloudFront.
    |
    */

    'url' => env('APP_URL', 'http://localhost:23080'),

    /*
    |--------------------------------------------------------------------------
    | Zona horaria y localización
    |--------------------------------------------------------------------------
    |
    | Las fechas de evaluación se almacenan en UTC y la interfaz trabaja con el
    | idioma español para los mensajes de validación de los datos clínicos.
    |
    */

    'timezone' => 'UTC',

    'locale' => env('APP_LOCALE', 'es'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'es'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'es_ES'),

    /*
    |--------------------------------------------------------------------------
    | Cifrado de la aplicación
    |--------------------------------------------------------------------------
    |
    | La clave se inyecta desde AWS Secrets Manager en producción; nunca se
    | versiona en el repositorio.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo mantenimiento
    |--------------------------------------------------------------------------
    |
    | El estado se comparte entre las tareas de ECS/Fargate mediante el caché
    | compartido de ElastiCache para Redis.
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'redis'),
    ],

];
