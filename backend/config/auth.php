<?php

use App\Infrastructure\Auth\CognitoUserProvider;
use App\Infrastructure\Persistence\Eloquent\Models\Usuario;

return [

    /*
    |--------------------------------------------------------------------------
    | Valores por defecto de autenticación
    |--------------------------------------------------------------------------
    |
    | El backend expone únicamente una API stateless: el guard por defecto es
    | "api" y los tokens Bearer son emitidos por Amazon Cognito y validados
    | con Laravel Passport.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'usuarios'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards de autenticación
    |--------------------------------------------------------------------------
    |
    | El guard "api" valida el token Bearer de la petición (auth:api) y resuelve
    | el usuario contra la tabla USUARIO. El guard "cognito" delega la
    | resolución del usuario de Cognito en el proveedor CognitoUserProvider,
    | que contrasta los claims del token con la entidad USUARIO.
    |
    */

    'guards' => [
        'api' => [
            'driver' => 'passport',
            'provider' => 'usuarios',
        ],

        'cognito' => [
            'driver' => 'passport',
            'provider' => 'cognito',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proveedores de usuarios
    |--------------------------------------------------------------------------
    |
    | "usuarios" carga la entidad USUARIO desde MySQL 8.0 con claves UUID.
    | "cognito" resuelve el usuario autenticado en Cognito contra la misma
    | entidad USUARIO para los claims de email y rol; su driver se registra
    | desde el módulo de autenticación (App\Infrastructure\Auth).
    |
    */

    'providers' => [
        'usuarios' => [
            'driver' => 'eloquent',
            'model' => Usuario::class,
        ],

        'cognito' => [
            'driver' => 'cognito',
            'model' => CognitoUserProvider::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restablecimiento de contraseñas
    |--------------------------------------------------------------------------
    |
    | La autenticación real se delega en Amazon Cognito; este bloque solo
    | configura el broker para las operaciones administrativas internas.
    |
    */

    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiempo de confirmación de contraseña
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
