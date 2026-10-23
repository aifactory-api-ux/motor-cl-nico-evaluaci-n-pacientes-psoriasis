<?php

namespace App\Infrastructure\AWS;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Publica logs estructurados en Amazon CloudWatch Logs.
 *
 * El motor de elegibilidad y la auditoría del protocolo ALM-PSO-2026 emiten
 * sus eventos con este cliente: cada entrada viaja como un objeto JSON con el
 * nivel de severidad, el mensaje y el contexto estructurado, de modo que
 * Logs Insights permita consultar y correlacionar las decisiones tomadas por
 * el sistema (NFR006).
 *
 * El registro nunca interrumpe el flujo de la aplicación: si el destino de
 * logs no está disponible, la entrada se deriva al canal de logging de
 * Laravel para no perder la traza.
 */
final class CloudWatchLogger
{
    /**
     * Niveles de severidad admitidos por Amazon CloudWatch Logs.
     *
     * @var list<string>
     */
    private const NIVELES = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * Nivel aplicado cuando el solicitado no está admitido.
     */
    private const NIVEL_POR_DEFECTO = 'info';

    /**
     * Identificador del servicio en los logs estructurados.
     */
    private const SERVICIO = 'motor-clinico-psoriasis';

    /**
     * Protocolo clínico al que pertenecen las decisiones registradas.
     */
    private const PROTOCOLO = 'ALM-PSO-2026';

    /**
     * Retenciones válidas para un grupo de logs de CloudWatch, en días.
     *
     * @var list<int>
     */
    private const RETENCIONES = [1, 3, 5, 7, 14, 30, 60, 90, 120, 150, 180, 365, 400, 545, 731, 1096, 1827, 2192, 2557, 2922, 3288, 3653];

    /**
     * Retención aplicada cuando la configurada no está admitida.
     */
    private const RETENCION_POR_DEFECTO = 30;

    /**
     * Claves de contexto cuyo valor nunca se publica.
     *
     * @var list<string>
     */
    private const CLAVES_SENSIBLES = [
        'password',
        'contrasena',
        'secret',
        'token',
        'authorization',
        'api_key',
        'apikey',
        'credential',
        'credencial',
    ];

    /**
     * Cliente de Amazon CloudWatch Logs reutilizado entre llamadas.
     */
    private static ?CloudWatchLogsClient $cliente = null;

    /**
     * Indica si el grupo y la secuencia de logs ya fueron creados.
     */
    private static bool $destinoPreparado = false;

    /**
     * Publica una entrada estructurada en Amazon CloudWatch Logs.
     *
     * @param  array<string, mixed>  $context
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        try {
            $nivel = self::normalizarNivel($level);
            $entrada = self::construirEntrada($nivel, $message, $context);

            if (self::publicarEnCloudWatch($entrada)) {
                return;
            }

            self::publicarEnLaravel($nivel, $message, $context);
        } catch (Throwable) {
            // El registro de la traza nunca debe propagar un fallo al negocio.
        }
    }

    /**
     * Construye la entrada estructurada que se publica en CloudWatch Logs.
     *
     * @param  array<string, mixed>  $contexto
     * @return array<string, mixed>
     */
    private static function construirEntrada(string $nivel, string $mensaje, array $contexto): array
    {
        return [
            'timestamp' => gmdate('Y-m-d\TH:i:s.v\Z'),
            'nivel' => $nivel,
            'servicio' => self::SERVICIO,
            'protocolo' => self::PROTOCOLO,
            'mensaje' => $mensaje,
            'contexto' => self::normalizarContexto($contexto),
        ];
    }

    /**
     * Publica la entrada en el grupo y la secuencia configurados.
     *
     * @param  array<string, mixed>  $entrada
     */
    private static function publicarEnCloudWatch(array $entrada): bool
    {
        $grupo = self::logGroup();
        $flujo = self::logStream();

        if ($grupo === null || $flujo === null) {
            return false;
        }

        $cliente = self::cliente();

        if ($cliente === null) {
            return false;
        }

        $mensaje = json_encode($entrada, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($mensaje)) {
            return false;
        }

        try {
            self::asegurarDestino($cliente, $grupo, $flujo);

            $cliente->putLogEvents([
                'logGroupName' => $grupo,
                'logStreamName' => $flujo,
                'logEvents' => [
                    [
                        'timestamp' => (int) round(microtime(true) * 1000),
                        'message' => $mensaje,
                    ],
                ],
            ]);
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Crea el grupo y la secuencia de logs cuando todavía no existen.
     */
    private static function asegurarDestino(CloudWatchLogsClient $cliente, string $grupo, string $flujo): void
    {
        if (self::$destinoPreparado) {
            return;
        }

        try {
            $cliente->createLogGroup([
                'logGroupName' => $grupo,
                'retentionInDays' => self::retentionDays(),
            ]);
        } catch (Throwable) {
            // El grupo ya existe o el servicio no lo permite: se continúa con el flujo.
        }

        try {
            $cliente->createLogStream([
                'logGroupName' => $grupo,
                'logStreamName' => $flujo,
            ]);
        } catch (Throwable) {
            // La secuencia ya existe: se continúa con el flujo.
        }

        self::$destinoPreparado = true;
    }

    /**
     * Deriva la entrada al canal de logging de Laravel.
     *
     * @param  array<string, mixed>  $contexto
     */
    private static function publicarEnLaravel(string $nivel, string $mensaje, array $contexto): void
    {
        $detalle = [
            'servicio' => self::SERVICIO,
            'protocolo' => self::PROTOCOLO,
            'contexto' => self::normalizarContexto($contexto),
        ];

        try {
            Log::channel('stderr')->log($nivel, $mensaje, $detalle);
        } catch (Throwable) {
            try {
                Log::log($nivel, $mensaje, $detalle);
            } catch (Throwable) {
                // Sin canal de logging disponible: la traza se descarta de forma silenciosa.
            }
        }
    }

    /**
     * Normaliza el nivel de severidad solicitado.
     */
    private static function normalizarNivel(string $nivel): string
    {
        $nivel = strtolower(trim($nivel));

        return in_array($nivel, self::NIVELES, true) ? $nivel : self::NIVEL_POR_DEFECTO;
    }

    /**
     * Convierte el contexto en una estructura publicable y sin credenciales.
     */
    private static function normalizarContexto(mixed $contexto, int $profundidad = 0): mixed
    {
        if ($contexto === null || is_scalar($contexto)) {
            return $contexto;
        }

        if ($contexto instanceof Throwable) {
            return [
                'excepcion' => $contexto::class,
                'mensaje' => $contexto->getMessage(),
            ];
        }

        if (is_object($contexto)) {
            return self::normalizarContexto((array) $contexto, $profundidad + 1);
        }

        if (! is_array($contexto)) {
            return null;
        }

        if ($profundidad > 5) {
            return '[...]';
        }

        $normalizado = [];

        foreach ($contexto as $clave => $valor) {
            $nombre = (string) $clave;

            if (self::esClaveSensible($nombre)) {
                $normalizado[$nombre] = '[REDACTADO]';

                continue;
            }

            $normalizado[$nombre] = self::normalizarContexto($valor, $profundidad + 1);
        }

        return $normalizado;
    }

    /**
     * Determina si el valor de la clave debe ocultarse en los logs.
     */
    private static function esClaveSensible(string $clave): bool
    {
        $normalizada = strtolower($clave);

        foreach (self::CLAVES_SENSIBLES as $sensible) {
            if (str_contains($normalizada, $sensible)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Construye el cliente de Amazon CloudWatch Logs.
     */
    private static function cliente(): ?CloudWatchLogsClient
    {
        if (self::$cliente instanceof CloudWatchLogsClient) {
            return self::$cliente;
        }

        if (! class_exists(CloudWatchLogsClient::class)) {
            return null;
        }

        try {
            self::$cliente = new CloudWatchLogsClient([
                'version' => 'latest',
                'region' => self::region(),
            ]);
        } catch (Throwable) {
            self::$cliente = null;
        }

        return self::$cliente;
    }

    /**
     * Grupo de logs configurado para el backend.
     */
    private static function logGroup(): ?string
    {
        $grupo = self::config('logging.cloudwatch.log_group');

        if (! is_string($grupo) || trim($grupo) === '') {
            $grupo = self::entorno('AWS_CLOUDWATCH_LOG_GROUP');
        }

        return is_string($grupo) && trim($grupo) !== '' ? trim($grupo) : null;
    }

    /**
     * Secuencia de logs configurada para el backend.
     */
    private static function logStream(): ?string
    {
        $flujo = self::config('logging.cloudwatch.log_stream');

        if (! is_string($flujo) || trim($flujo) === '') {
            $flujo = self::entorno('AWS_CLOUDWATCH_LOG_STREAM');
        }

        return is_string($flujo) && trim($flujo) !== '' ? trim($flujo) : null;
    }

    /**
     * Región de AWS donde se publican los logs.
     */
    private static function region(): string
    {
        $region = self::config('logging.cloudwatch.region');

        if (! is_string($region) || trim($region) === '') {
            $region = self::entorno('AWS_REGION');
        }

        return is_string($region) && trim($region) !== '' ? trim($region) : 'us-east-1';
    }

    /**
     * Retención aplicada al grupo de logs, en días.
     */
    private static function retentionDays(): int
    {
        $dias = self::config('logging.cloudwatch.retention_days');

        if (! is_int($dias) && ! is_numeric($dias)) {
            return self::RETENCION_POR_DEFECTO;
        }

        $dias = (int) $dias;

        return in_array($dias, self::RETENCIONES, true) ? $dias : self::RETENCION_POR_DEFECTO;
    }

    /**
     * Lee un valor de la configuración de la aplicación.
     */
    private static function config(string $clave, mixed $porDefecto = null): mixed
    {
        try {
            $valor = config($clave, $porDefecto);
        } catch (Throwable) {
            return $porDefecto;
        }

        return $valor ?? $porDefecto;
    }

    /**
     * Lee un valor de la configuración del entorno del proceso.
     */
    private static function entorno(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = getenv($clave);

        if (! is_string($valor) || trim($valor) === '') {
            $valor = $_ENV[$clave] ?? $_SERVER[$clave] ?? null;
        }

        if (! is_string($valor) || trim($valor) === '') {
            try {
                $valor = env($clave, $porDefecto);
            } catch (Throwable) {
                $valor = $porDefecto;
            }
        }

        if (! is_string($valor)) {
            return $porDefecto;
        }

        $valor = trim($valor);

        return $valor === '' ? $porDefecto : $valor;
    }
}
