<?php

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Canal de logs por defecto
    |--------------------------------------------------------------------------
    |
    | El backend corre como tarea de ECS/Fargate: el driver awslogs envía la
    | salida de stderr a Amazon CloudWatch Logs. El formato JSON estructurado
    | permite analizar nivel, mensaje y contexto con Logs Insights (NFR006).
    |
    */

    'default' => env('LOG_CHANNEL', 'stderr'),

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => (bool) env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Canales de logging
    |--------------------------------------------------------------------------
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK_CHANNELS', 'stderr')),
            'ignore_exceptions' => false,
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'info'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => JsonFormatter::class,
            'formatter_with' => [
                'batchMode' => JsonFormatter::BATCH_MODE_JSON,
                'appendNewline' => true,
            ],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'info'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon CloudWatch Logs
    |--------------------------------------------------------------------------
    |
    | Grupo y secuencia de logs donde CloudWatchLogger emite las entradas
    | estructuradas del motor de elegibilidad y de la auditoría.
    |
    */

    'cloudwatch' => [
        'log_group' => env('AWS_CLOUDWATCH_LOG_GROUP'),
        'log_stream' => env('AWS_CLOUDWATCH_LOG_STREAM'),
        'region' => env('AWS_REGION', 'us-east-1'),
        'retention_days' => (int) env('AWS_CLOUDWATCH_RETENTION_DAYS', 30),
    ],

];
