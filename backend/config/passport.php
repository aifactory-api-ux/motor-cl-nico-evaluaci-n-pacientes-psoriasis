<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Guard de Passport
    |--------------------------------------------------------------------------
    |
    | El guard "api" es el que valida el token Bearer de las rutas protegidas
    | con el middleware auth:api.
    |
    */

    'guard' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Claves de cifrado
    |--------------------------------------------------------------------------
    |
    | Las claves privadas y públicas de Passport se inyectan en el arranque
    | desde AWS Secrets Manager; nunca se versionan en el repositorio.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Conexión de base de datos de Passport
    |--------------------------------------------------------------------------
    |
    | Vacío significa que Passport usa la conexión por defecto de la
    | aplicación, es decir, Amazon RDS para MySQL 8.0.
    |
    */

    'connection' => env('PASSPORT_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Identificadores de cliente
    |--------------------------------------------------------------------------
    |
    | La arquitectura del proyecto prohíbe identificadores autoincrementales,
    | por lo que los clientes de Passport también se identifican con UUID.
    |
    */

    'client_uuids' => true,

    /*
    |--------------------------------------------------------------------------
    | Cliente de acceso personal
    |--------------------------------------------------------------------------
    |
    | Amazon Cognito emite los tokens JWT que consume auth:api; este cliente
    | queda disponible para los flujos internos de Passport.
    |
    */

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

];
