<?php

namespace App\Infrastructure\AWS;

use Aws\AppConfig\AppConfigClient;
use RuntimeException;
use Throwable;

/**
 * Lee los parámetros dinámicos del motor en AWS AppConfig.
 *
 * AppConfig entrega sin reiniciar la tarea de ECS/Fargate los parámetros de
 * operación del protocolo ALM-PSO-2026, por ejemplo el TTL de caché de la
 * resolución de elegibilidad (EVALUACION_ENGINE_CACHE_TTL) y los indicadores
 * de habilitación de cada regla clínica.
 *
 * Si el servicio no está disponible, el cliente registra el fallo en Amazon
 * CloudWatch sin exponer información sensible y la resolución termina con un
 * error controlado para que la tarea no arranque con una configuración
 * incompleta (NFR010).
 */
final class AppConfigClient
{
    /**
     * Máximo número de consultas durante una misma sesión de configuración.
     */
    private const MAXIMO_INTENTOS = 5;

    /**
     * Espera entre consultas de una misma sesión, en microsegundos.
     */
    private const ESPERA_SESION_US = 200000;

    /**
     * Cliente de AWS AppConfig reutilizado entre llamadas.
     */
    private static ?AppConfigClient $cliente = null;

    /**
     * Configuraciones ya resueltas, indexadas por perfil.
     *
     * @var array<string, array{valor: array<string, mixed>, expira_en: int}>
     */
    private static array $cache = [];

    /**
     * Obtiene los parámetros de una configuración de AWS AppConfig.
     *
     * Si el identificador de configuración es vacío se resuelve el perfil
     * declarado en config/appconfig.php. Los parámetros por defecto del
     * backend se aplican siempre como respaldo de los valores remotos.
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(string $configuration): array
    {
        $perfil = trim($configuration);

        if ($perfil === '') {
            $perfil = self::configurationProfile();
        }

        if (! self::habilitado() || $perfil === '') {
            return self::parametrosPorDefecto();
        }

        $cacheado = self::deCache($perfil);

        if ($cacheado !== null) {
            return $cacheado;
        }

        $valores = array_merge(self::parametrosPorDefecto(), $this->descargar($perfil));

        self::$cache[$perfil] = [
            'valor' => $valores,
            'expira_en' => time() + self::pollInterval(),
        ];

        return $valores;
    }

    /**
     * Obtiene un parámetro concreto de la configuración remota.
     */
    public function getParameter(string $nombre, mixed $porDefecto = null): mixed
    {
        $configuracion = $this->getConfiguration('');

        if (! array_key_exists($nombre, $configuracion)) {
            return $porDefecto;
        }

        return $configuracion[$nombre];
    }

    /**
     * Descarta la configuración cacheada para forzar una nueva lectura remota.
     */
    public function refresh(): void
    {
        self::$cache = [];
    }

    /**
     * Descarga la configuración remota del perfil solicitado.
     *
     * @return array<string, mixed>
     */
    private function descargar(string $perfil): array
    {
        $cliente = self::cliente();

        if ($cliente === null) {
            throw new RuntimeException('No fue posible inicializar el cliente de AWS AppConfig.');
        }

        $aplicacion = self::application();
        $ambiente = self::environment();

        if ($aplicacion === '' || $ambiente === '') {
            throw new RuntimeException('Faltan el identificador de aplicación o de ambiente de AWS AppConfig.');
        }

        try {
            $sesion = $cliente->startConfigurationSession([
                'ApplicationIdentifier' => $aplicacion,
                'EnvironmentIdentifier' => $ambiente,
                'ConfigurationProfileIdentifier' => $perfil,
            ]);
        } catch (Throwable $e) {
            self::registrarFallo($perfil, $e);

            throw new RuntimeException('No fue posible iniciar la sesión de configuración en AWS AppConfig.', 0, $e);
        }

        $token = $sesion['InitialConfigurationToken'] ?? null;

        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('AWS AppConfig no devolvió un token de sesión de configuración válido.');
        }

        $cuerpo = '';

        try {
            for ($intento = 0; $intento < self::MAXIMO_INTENTOS; $intento++) {
                $respuesta = $cliente->getLatestConfiguration([
                    'ConfigurationToken' => $token,
                ]);

                $fragmento = $respuesta['Configuration'] ?? null;

                if (is_string($fragmento) && trim($fragmento) !== '') {
                    $cuerpo = $fragmento;
                }

                $siguiente = $respuesta['NextPollConfigurationToken'] ?? null;

                if (! is_string($siguiente) || trim($siguiente) === '') {
                    break;
                }

                $token = $siguiente;

                usleep(self::ESPERA_SESION_US);
            }
        } catch (Throwable $e) {
            self::registrarFallo($perfil, $e);

            throw new RuntimeException('No fue posible leer la configuración de AWS AppConfig.', 0, $e);
        }

        if (trim($cuerpo) === '') {
            return [];
        }

        $decodificado = json_decode($cuerpo, true);

        return is_array($decodificado) ? $decodificado : [];
    }

    /**
     * Parámetros declarados por defecto en config/appconfig.php.
     *
     * @return array<string, mixed>
     */
    private static function parametrosPorDefecto(): array
    {
        $parametros = self::config('appconfig.parameters');

        return is_array($parametros) ? $parametros : [];
    }

    /**
     * Recupera la configuración de la caché en memoria cuando sigue vigente.
     *
     * @return array<string, mixed>|null
     */
    private static function deCache(string $perfil): ?array
    {
        $entrada = self::$cache[$perfil] ?? null;

        if ($entrada === null) {
            return null;
        }

        if ($entrada['expira_en'] <= time()) {
            unset(self::$cache[$perfil]);

            return null;
        }

        return $entrada['valor'];
    }

    /**
     * Construye el cliente de AWS AppConfig.
     */
    private static function cliente(): ?AppConfigClient
    {
        if (self::$cliente instanceof AppConfigClient) {
            return self::$cliente;
        }

        if (! class_exists(AppConfigClient::class)) {
            return null;
        }

        $configuracion = [
            'version' => 'latest',
            'region' => self::region(),
        ];

        $endpoint = self::config('appconfig.endpoint');

        if (is_string($endpoint) && trim($endpoint) !== '') {
            $configuracion['endpoint'] = trim($endpoint);
        }

        try {
            self::$cliente = new AppConfigClient($configuracion);
        } catch (Throwable) {
            self::$cliente = null;
        }

        return self::$cliente;
    }

    /**
     * Registra en Amazon CloudWatch el fallo al leer la configuración.
     */
    private static function registrarFallo(string $perfil, Throwable $e): void
    {
        if (! class_exists(CloudWatchLogger::class)) {
            return;
        }

        try {
            CloudWatchLogger::write('error', 'No fue posible obtener los parámetros de AWS AppConfig.', [
                'configuration_profile' => $perfil,
                'excepcion' => $e::class,
                'motivo' => $e->getMessage(),
            ]);
        } catch (Throwable) {
            // El registro del fallo nunca debe propagar el error original.
        }
    }

    /**
     * Identificador de la aplicación en AWS AppConfig.
     */
    private static function application(): string
    {
        $aplicacion = self::config('appconfig.application');

        if (! is_string($aplicacion) || trim($aplicacion) === '') {
            $aplicacion = self::entorno('AWS_APPCONFIG_APPLICATION');
        }

        return is_string($aplicacion) ? trim($aplicacion) : '';
    }

    /**
     * Identificador del ambiente en AWS AppConfig.
     */
    private static function environment(): string
    {
        $ambiente = self::config('appconfig.environment');

        if (! is_string($ambiente) || trim($ambiente) === '') {
            $ambiente = self::entorno('AWS_APPCONFIG_ENVIRONMENT');
        }

        return is_string($ambiente) ? trim($ambiente) : '';
    }

    /**
     * Perfil de configuración consultado por defecto.
     */
    private static function configurationProfile(): string
    {
        $perfil = self::config('appconfig.configuration_profile');

        if (! is_string($perfil) || trim($perfil) === '') {
            $perfil = self::entorno('AWS_APPCONFIG_CONFIGURATION_PROFILE');
        }

        return is_string($perfil) ? trim($perfil) : '';
    }

    /**
     * Frecuencia de lectura de la configuración remota, en segundos.
     */
    private static function pollInterval(): int
    {
        $intervalo = self::config('appconfig.poll_interval');

        if (! is_int($intervalo) && ! is_numeric($intervalo)) {
            return 300;
        }

        $intervalo = (int) $intervalo;

        return $intervalo > 0 ? $intervalo : 300;
    }

    /**
     * Región de AWS donde reside la configuración.
     */
    private static function region(): string
    {
        $region = self::config('appconfig.region');

        if (! is_string($region) || trim($region) === '') {
            $region = self::entorno('AWS_REGION');
        }

        return is_string($region) && trim($region) !== '' ? trim($region) : 'us-east-1';
    }

    /**
     * Indica si la integración con AppConfig está habilitada.
     */
    private static function habilitado(): bool
    {
        $habilitado = self::config('appconfig.enabled');

        if (is_bool($habilitado)) {
            return $habilitado;
        }

        if (is_string($habilitado)) {
            return in_array(strtolower(trim($habilitado)), ['1', 'true', 'on', 'yes', 'si'], true);
        }

        $entorno = self::entorno('AWS_APPCONFIG_ENABLED');

        return $entorno !== null && in_array(strtolower($entorno), ['1', 'true', 'on', 'yes', 'si'], true);
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
