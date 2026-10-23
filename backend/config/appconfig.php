<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integración con AWS AppConfig
    |--------------------------------------------------------------------------
    |
    | AppConfig entrega los parámetros dinámicos del motor (por ejemplo, el
    | TTL de caché de la resolución de elegibilidad) sin reiniciar las tareas
    | de ECS/Fargate. Si el servicio no está disponible, el arranque falla de
    | forma controlada y el error se registra en Amazon CloudWatch (NFR010).
    |
    */

    'enabled' => (bool) env('AWS_APPCONFIG_ENABLED', false),

    'region' => env('AWS_REGION', 'us-east-1'),

    /*
    |--------------------------------------------------------------------------
    | Aplicación, ambiente y perfil de configuración
    |--------------------------------------------------------------------------
    |
    | Cada ambiente de despliegue tiene su propia aplicación y su propio
    | perfil de configuración en AppConfig.
    |
    */

    'application' => env('AWS_APPCONFIG_APPLICATION'),

    'environment' => env('AWS_APPCONFIG_ENVIRONMENT'),

    'configuration_profile' => env('AWS_APPCONFIG_CONFIGURATION_PROFILE', 'application'),

    /*
    |--------------------------------------------------------------------------
    | Frecuencia de lectura
    |--------------------------------------------------------------------------
    |
    | Segundos entre lecturas sucesivas de la configuración remota.
    |
    */

    'poll_interval' => (int) env('AWS_APPCONFIG_POLL_INTERVAL', 300),

    /*
    |--------------------------------------------------------------------------
    | Estrategia de despliegue
    |--------------------------------------------------------------------------
    |
    | Identificador de la estrategia de despliegue de AppConfig; vacío
    | significa que se usa la estrategia predeterminada de la aplicación.
    |
    */

    'deployment_strategy' => env('AWS_APPCONFIG_DEPLOYMENT_STRATEGY'),

    /*
    |--------------------------------------------------------------------------
    | Parámetros por defecto
    |--------------------------------------------------------------------------
    |
    | Valores de respaldo que se aplican cuando AppConfig no entrega un
    | parámetro concreto.
    |
    */

    'parameters' => [
        'EVALUACION_ENGINE_CACHE_TTL' => (int) env('EVALUACION_ENGINE_CACHE_TTL', 300),
    ],

];
