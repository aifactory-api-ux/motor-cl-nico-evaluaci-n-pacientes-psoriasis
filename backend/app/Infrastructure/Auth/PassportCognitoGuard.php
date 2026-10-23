<?php

namespace App\Infrastructure\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

/**
 * Guard de la API respaldado por Laravel Passport y Amazon Cognito.
 *
 * Las rutas del protocolo ALM-PSO-2026 se protegen con el middleware
 * auth:api. Este guard toma el token Bearer de la petición, lo valida con el
 * decodificador de Cognito (firma RS256 contra el JWKS del user pool) y
 * resuelve el usuario autenticado contra la tabla USUARIO a partir de los
 * claims del token.
 *
 * Cuando el token falta o no es válido se lanza una AuthenticationException,
 * que el gestor de excepciones de la API traduce en una respuesta 401
 * (NFR004). El guard no emite ni persiste tokens: Cognito es la única fuente
 * de identidad y Passport valida el token Bearer.
 */
final class PassportCognitoGuard implements Guard
{
    use GuardHelpers;

    /**
     * Claims del token Bearer autenticado en la petición actual.
     *
     * @var array<string, mixed>|null
     */
    private ?array $claims = null;

    /**
     * Indica si la identidad de la petición ya se resolvió con éxito.
     */
    private bool $resuelto = false;

    /**
     * @param  CognitoJWTDecoder  $decoder  Valida la firma y los claims del token Bearer.
     * @param  CognitoUserProvider  $userProvider  Resuelve el usuario del protocolo desde los claims.
     * @param  Request  $request  Petición que transporta la cabecera Authorization.
     */
    public function __construct(
        private readonly CognitoJWTDecoder $decoder,
        private readonly CognitoUserProvider $userProvider,
        private readonly Request $request
    ) {
        $this->provider = $userProvider;
    }

    /**
     * Construye el guard con el decodificador y el proveedor del contenedor.
     */
    public static function resolve(?Request $request = null): self
    {
        $peticion = $request ?? request();

        return new self(
            app(CognitoJWTDecoder::class),
            app(CognitoUserProvider::class),
            $peticion
        );
    }

    /**
     * Obtiene el usuario autenticado a partir del token Bearer de la petición.
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->resuelto) {
            return null;
        }

        $token = $this->tokenBearer();

        if ($token === null) {
            throw new AuthenticationException('No autenticado. Se requiere un token Bearer válido.');
        }

        $claims = $this->decoder->decode($token);

        $usuario = $this->userProvider->resolverPorClaims($claims);

        if ($usuario === null) {
            throw new AuthenticationException('El usuario autenticado no está registrado en el protocolo ALM-PSO-2026.');
        }

        $this->claims = $claims;
        $this->resuelto = true;

        return $this->user = $usuario;
    }

    /**
     * Identificador del usuario autenticado.
     */
    public function id()
    {
        $usuario = $this->user();

        return $usuario?->getAuthIdentifier();
    }

    /**
     * Claims del token Bearer autenticado.
     *
     * @return array<string, mixed>
     */
    public function claims(): array
    {
        $this->user();

        return $this->claims ?? [];
    }

    /**
     * Extrae el token Bearer de la cabecera Authorization.
     */
    private function tokenBearer(): ?string
    {
        $token = $this->request->bearerToken();

        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        return trim($token);
    }
}
