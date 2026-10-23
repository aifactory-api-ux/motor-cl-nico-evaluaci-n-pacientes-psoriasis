<?php

namespace App\Infrastructure\Auth;

use App\Infrastructure\AWS\CloudWatchLogger;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Decodifica y valida los tokens JWT emitidos por Amazon Cognito.
 *
 * La validación sigue el flujo de Cognito: se descarga el conjunto de claves
 * públicas (JWKS) del user pool, se verifica la firma RS256 del token y se
 * contrastan los claims de emisor y audiencia contra la configuración del
 * pool ALM-PSO-2026. Los tokens inválidos o expirados producen una
 * AuthenticationException, que el gestor de excepciones de la API traduce a
 * una respuesta 401.
 */
final class CognitoJWTDecoder
{
    /**
     * Algoritmo de firma que Cognito aplica a los tokens del pool.
     */
    private const ALGORITMO = 'RS256';

    /**
     * Tiempo máximo de espera para descargar el conjunto de claves públicas.
     */
    private const TIMEOUT_HTTP = 5;

    /**
     * Intentos de descarga del conjunto de claves públicas.
     */
    private const REINTENTOS_HTTP = 2;

    /**
     * Espera entre reintentos de descarga, en milisegundos.
     */
    private const ESPERA_REINTENTO_MS = 200;

    /**
     * Tipos de token emitidos por Cognito que el backend admite.
     */
    private const TIPOS_TOKEN = ['id', 'access'];

    /**
     * Conjunto de claves públicas descargado de Cognito.
     *
     * @var array<string, Key>|null
     */
    private ?array $clavesPublicas = null;

    /**
     * @param  string|null  $jwksUrl  URL del JWKS; si es nula se resuelve con la configuración del entorno.
     * @param  string|null  $issuer  Emisor esperado; si es nulo se deriva del user pool.
     * @param  string|null  $audience  Audiencia esperada; si es nula se resuelve con el client id.
     */
    public function __construct(
        private readonly ?string $jwksUrl = null,
        private readonly ?string $issuer = null,
        private readonly ?string $audience = null
    ) {
    }

    /**
     * Valida un token Bearer emitido por Amazon Cognito y devuelve sus claims.
     *
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        $token = trim($token);

        if ($token === '') {
            throw new AuthenticationException('Token de autenticación ausente.');
        }

        if (substr_count($token, '.') !== 2) {
            throw new AuthenticationException('Token de autenticación con formato inválido.');
        }

        $claves = $this->clavesPublicas();

        try {
            $claims = JWT::decode($token, $claves);
        } catch (Throwable $e) {
            $this->registrar('warning', 'Token de Cognito rechazado durante la validación de la firma.', [
                'excepcion' => $e::class,
                'motivo' => $e->getMessage(),
            ]);

            throw new AuthenticationException('Token de autenticación inválido o expirado.');
        }

        $datos = $this->normalizarClaims($claims);

        $this->validarClaims($datos);

        return $datos;
    }

    /**
     * Valida los claims que el protocolo de Cognito exige en cada token.
     *
     * @param  array<string, mixed>  $datos
     */
    private function validarClaims(array $datos): void
    {
        $sub = $datos['sub'] ?? null;

        if (! is_string($sub) || trim($sub) === '') {
            throw new AuthenticationException('El token de autenticación no identifica a un usuario.');
        }

        if (! isset($datos['exp']) || ! is_numeric($datos['exp'])) {
            throw new AuthenticationException('El token de autenticación no declara su expiración.');
        }

        $tipo = $datos['token_use'] ?? null;

        if (is_string($tipo) && ! in_array($tipo, self::TIPOS_TOKEN, true)) {
            throw new AuthenticationException('El tipo de token de Cognito no está admitido.');
        }

        $this->validarIssuer($datos);
        $this->validarAudience($datos);
    }

    /**
     * Comprueba que el token proceda del user pool configurado.
     *
     * @param  array<string, mixed>  $datos
     */
    private function validarIssuer(array $datos): void
    {
        $esperado = $this->issuerEsperado();
        $issuer = $datos['iss'] ?? null;

        if (! is_string($issuer) || ! hash_equals($esperado, $issuer)) {
            $this->registrar('warning', 'El token de Cognito fue emitido por un proveedor no autorizado.', [
                'issuer' => is_string($issuer) ? $issuer : null,
            ]);

            throw new AuthenticationException('Token de autenticación emitido por un proveedor no autorizado.');
        }
    }

    /**
     * Comprueba que el token haya sido emitido para el cliente configurado.
     *
     * @param  array<string, mixed>  $datos
     */
    private function validarAudience(array $datos): void
    {
        $clientId = $this->clientId();
        $aud = $datos['aud'] ?? null;
        $audiencias = is_array($aud) ? array_map('strval', $aud) : (is_string($aud) ? [$aud] : []);

        if (! in_array($clientId, $audiencias, true)) {
            $this->registrar('warning', 'El token de Cognito no fue emitido para el cliente configurado.', []);

            throw new AuthenticationException('Token de autenticación emitido para otra aplicación.');
        }
    }

    /**
     * Devuelve las claves públicas del user pool, descargándolas una sola vez
     * por instancia.
     *
     * @return array<string, Key>
     */
    private function clavesPublicas(): array
    {
        if ($this->clavesPublicas !== null) {
            return $this->clavesPublicas;
        }

        $url = $this->urlJwks();

        try {
            $respuesta = Http::acceptJson()
                ->timeout(self::TIMEOUT_HTTP)
                ->retry(self::REINTENTOS_HTTP, self::ESPERA_REINTENTO_MS, null, false)
                ->get($url);
        } catch (Throwable $e) {
            $this->registrar('error', 'No fue posible descargar las claves públicas de Cognito.', [
                'excepcion' => $e::class,
                'motivo' => $e->getMessage(),
            ]);

            throw new RuntimeException('No fue posible descargar las claves públicas de Amazon Cognito.', 0, $e);
        }

        if (! $respuesta->successful()) {
            throw new RuntimeException(sprintf(
                'Amazon Cognito respondió con estado %d al descargar las claves públicas.',
                $respuesta->status()
            ));
        }

        $documento = $respuesta->json();

        if (! is_array($documento) || ! isset($documento['keys']) || ! is_array($documento['keys'])) {
            throw new RuntimeException('El conjunto de claves públicas de Amazon Cognito tiene un formato inesperado.');
        }

        try {
            $claves = JWK::parseKeySet($documento, self::ALGORITMO);
        } catch (Throwable $e) {
            throw new RuntimeException('El conjunto de claves públicas de Amazon Cognito no pudo interpretarse.', 0, $e);
        }

        return $this->clavesPublicas = $claves;
    }

    /**
     * Resuelve la URL del conjunto de claves públicas del user pool.
     */
    private function urlJwks(): string
    {
        if (is_string($this->jwksUrl) && trim($this->jwksUrl) !== '') {
            return trim($this->jwksUrl);
        }

        $url = self::entorno('AWS_COGNITO_JWKS_URL');

        if (is_string($url) && trim($url) !== '') {
            return trim($url);
        }

        return sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s/.well-known/jwks.json',
            $this->region(),
            $this->userPoolId()
        );
    }

    /**
     * Emisor esperado en los tokens del user pool configurado.
     */
    private function issuerEsperado(): string
    {
        if (is_string($this->issuer) && trim($this->issuer) !== '') {
            return trim($this->issuer);
        }

        return sprintf(
            'https://cognito-idp.%s.amazonaws.com/%s',
            $this->region(),
            $this->userPoolId()
        );
    }

    /**
     * Client id de la aplicación registrada en el user pool.
     */
    private function clientId(): string
    {
        if (is_string($this->audience) && trim($this->audience) !== '') {
            return trim($this->audience);
        }

        $clientId = self::entorno('AWS_COGNITO_CLIENT_ID');

        if (! is_string($clientId) || trim($clientId) === '') {
            throw new RuntimeException(
                'Falta la variable de entorno AWS_COGNITO_CLIENT_ID requerida para validar los tokens de Cognito.'
            );
        }

        return trim($clientId);
    }

    /**
     * Identificador del user pool de Cognito.
     */
    private function userPoolId(): string
    {
        $poolId = self::entorno('AWS_COGNITO_USER_POOL_ID');

        if (! is_string($poolId) || trim($poolId) === '') {
            throw new RuntimeException(
                'Falta la variable de entorno AWS_COGNITO_USER_POOL_ID requerida para validar los tokens de Cognito.'
            );
        }

        return trim($poolId);
    }

    /**
     * Región de AWS donde reside el user pool.
     */
    private function region(): string
    {
        $region = self::entorno('AWS_REGION', 'us-east-1');

        if (! is_string($region) || trim($region) === '') {
            return 'us-east-1';
        }

        return trim($region);
    }

    /**
     * Lee un valor de la configuración del entorno.
     *
     * Se consultan primero las variables del proceso para que la lectura siga
     * siendo válida cuando la configuración de Laravel está cacheada y el
     * archivo .env no se carga.
     */
    private static function entorno(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = getenv($clave);

        if (! is_string($valor) || trim($valor) === '') {
            $valor = $_ENV[$clave] ?? $_SERVER[$clave] ?? null;
        }

        if (! is_string($valor) || trim($valor) === '') {
            $valor = env($clave, $porDefecto);
        }

        if (! is_string($valor)) {
            return $porDefecto;
        }

        $valor = trim($valor);

        return $valor === '' ? $porDefecto : $valor;
    }

    /**
     * Convierte los claims decodificados por la librería JWT en un arreglo.
     *
     * @return array<string, mixed>
     */
    private function normalizarClaims(object $claims): array
    {
        $datos = json_decode(json_encode($claims), true);

        return is_array($datos) ? $datos : [];
    }

    /**
     * Publica el evento en Amazon CloudWatch cuando el logger está disponible.
     *
     * @param  array<string, mixed>  $contexto
     */
    private function registrar(string $nivel, string $mensaje, array $contexto): void
    {
        if (! class_exists(CloudWatchLogger::class)) {
            return;
        }

        try {
            CloudWatchLogger::write($nivel, $mensaje, $contexto);
        } catch (Throwable) {
            // El registro en CloudWatch nunca debe interrumpir la autenticación.
        }
    }
}
