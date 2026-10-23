<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rutas con CORS
    |--------------------------------------------------------------------------
    |
    | Solo la API publicada bajo /api/v1 acepta peticiones cross-origin. El
    | frontend se sirve desde Amazon S3 detrás de CloudFront, por lo que el
    | origen permitido se configura por entorno.
    |
    */

    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Métodos permitidos
    |--------------------------------------------------------------------------
    |
    | La API expone lecturas, evaluaciones y exportaciones en formato JSON.
    |
    */

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Orígenes permitidos
    |--------------------------------------------------------------------------
    |
    | Acepta una lista separada por comas para cubrir los ambientes de
    | desarrollo, staging y producción (CloudFront) de la interfaz.
    |
    */

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173')))
    ))),

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Cabeceras permitidas
    |--------------------------------------------------------------------------
    |
    | Se admiten las cabeceras de autenticación que exige el token Bearer.
    |
    */

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 600),

    /*
    |--------------------------------------------------------------------------
    | Credenciales cross-origin
    |--------------------------------------------------------------------------
    |
    | La autenticación viaja en la cabecera Authorization y no en cookies, por
    | lo que no se admiten credenciales cross-origin.
    |
    */

    'supports_credentials' => false,

];
