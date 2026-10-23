<?php

namespace App\Infrastructure\Auth;

use App\Infrastructure\AWS\CloudWatchLogger;
use App\Infrastructure\Persistence\Eloquent\Models\Usuario;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Throwable;

/**
 * Resuelve el usuario autenticado por Amazon Cognito contra la tabla USUARIO.
 *
 * El proveedor implementa el contrato UserProvider de Laravel para que el
 * guard de la API pueda operar con el driver "cognito" declarado en
 * config/auth.php. La identidad se contrasta con el correo del token: si el
 * usuario no está dado de alta en USUARIO, la petición se rechaza porque las
 * tablas de evaluación y auditoría exigen una clave de usuario válida.
 *
 * Las firmas conservan los parámetros sin tipo del contrato UserProvider de
 * Laravel para mantener la compatibilidad de tipos con la interfaz: el
 * identificador es siempre el UUID de la tabla USUARIO.
 */
final class CognitoUserProvider implements UserProvider
{
    /**
     * Claims de Cognito que identifican al usuario por su correo.
     */
    private const CLAIMS_CORREO = ['email', 'cognito:username', 'preferred_username'];

    /**
     * Recupera el usuario por su identificador único (UUID de USUARIO).
     *
     * @param  mixed  $identifier
     */
    public function retrieveById($identifier): ?Usuario
    {
        $id = is_scalar($identifier) ? trim((string) $identifier) : '';

        if ($id === '') {
            return null;
        }

        return $this->usuarioPorId($id);
    }

    /**
     * Cognito no emite tokens de recordar persistidos por el backend.
     *
     * @param  mixed  $identifier
     * @param  mixed  $token
     */
    public function retrieveByToken($identifier, $token): ?Usuario
    {
        return null;
    }

    /**
     * La sesión del usuario la administra Cognito: USUARIO no almacena token
     * de recordar.
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $this->registrar('debug', 'Cognito administra la sesión: no se persiste token de recordar en USUARIO.', [
            'usuario_id' => $user->getAuthIdentifier(),
        ]);
    }

    /**
     * Recupera el usuario a partir de credenciales de correo.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Usuario
    {
        $correo = $credentials['email'] ?? null;

        if (! is_string($correo) || trim($correo) === '') {
            return null;
        }

        return $this->usuarioPorCorreo(trim($correo));
    }

    /**
     * La comprobación de credenciales se delega en Amazon Cognito.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return false;
    }

    /**
     * Resuelve el usuario de USUARIO a partir de los claims de un token de
     * Cognito ya validado.
     *
     * @param  array<string, mixed>  $claims
     */
    public function resolverPorClaims(array $claims): ?Usuario
    {
        $correo = $this->correoDeClaims($claims);

        if ($correo === null) {
            $this->registrar('warning', 'El token de Cognito no contiene un correo utilizable.', []);

            return null;
        }

        $usuario = $this->usuarioPorCorreo($correo);

        if ($usuario === null) {
            $this->registrar('warning', 'No existe un usuario USUARIO para el correo autenticado por Cognito.', [
                'correo' => $correo,
            ]);
        }

        return $usuario;
    }

    /**
     * Busca el usuario de USUARIO por su identificador único.
     */
    private function usuarioPorId(string $id): ?Usuario
    {
        $modelo = $this->modeloUsuario();

        if ($modelo === null) {
            return null;
        }

        $usuario = $modelo::query()->whereKey($id)->first();

        return $usuario instanceof Usuario ? $usuario : null;
    }

    /**
     * Busca el usuario de USUARIO por su correo.
     */
    private function usuarioPorCorreo(string $correo): ?Usuario
    {
        $modelo = $this->modeloUsuario();

        if ($modelo === null) {
            return null;
        }

        $usuario = $modelo::query()->where('email', $correo)->first();

        return $usuario instanceof Usuario ? $usuario : null;
    }

    /**
     * Extrae el correo del usuario de los claims del token de Cognito.
     *
     * @param  array<string, mixed>  $claims
     */
    private function correoDeClaims(array $claims): ?string
    {
        foreach (self::CLAIMS_CORREO as $clave) {
            $valor = $claims[$clave] ?? null;

            if (is_string($valor) && trim($valor) !== '') {
                return trim($valor);
            }
        }

        return null;
    }

    /**
     * Clase Eloquent que representa la tabla USUARIO, si está disponible.
     */
    private function modeloUsuario(): ?string
    {
        return class_exists(Usuario::class) ? Usuario::class : null;
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
