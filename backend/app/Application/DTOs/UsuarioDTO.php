<?php

namespace App\Application\DTOs;

use App\Domain\Entities\Enums\Rol;
use InvalidArgumentException;

/**
 * Contrato de datos del usuario del protocolo entre el backend y el frontend.
 *
 * Los campos coinciden uno a uno con la interfaz TypeScript UsuarioDTO.
 */
final class UsuarioDTO
{
    public string $id;

    public string $email;

    public string $nombre;

    public string $rol;

    public function __construct(
        string $id,
        string $email,
        string $nombre,
        Rol|string $rol
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->nombre = $nombre;
        $this->rol = self::normalizarRol($rol)->value;
    }

    /**
     * Construye el DTO a partir de una fila de base de datos o de un payload
     * asociativo con los nombres de campo del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        $rol = self::campo($datos, 'rol');

        return new self(
            id: (string) self::campo($datos, 'id'),
            email: (string) self::campo($datos, 'email'),
            nombre: (string) self::campo($datos, 'nombre'),
            rol: $rol instanceof Rol ? $rol : (string) $rol
        );
    }

    /**
     * Serializa el DTO con los nombres de campo del contrato de la API.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'nombre' => $this->nombre,
            'rol' => $this->rol,
        ];
    }

    /**
     * Obtiene un campo obligatorio del payload de origen.
     *
     * @param  array<string, mixed>  $datos
     */
    private static function campo(array $datos, string $clave): mixed
    {
        if (! array_key_exists($clave, $datos) || $datos[$clave] === null) {
            throw new InvalidArgumentException(sprintf(
                'Falta el campo "%s" para construir %s.',
                $clave,
                self::class
            ));
        }

        return $datos[$clave];
    }

    /**
     * Resuelve el rol del usuario a un valor admitido del dominio.
     */
    private static function normalizarRol(Rol|string $rol): Rol
    {
        if ($rol instanceof Rol) {
            return $rol;
        }

        $valor = Rol::tryFrom($rol);

        if ($valor === null) {
            throw new InvalidArgumentException(sprintf(
                'El rol "%s" no es un valor admitido para %s.',
                $rol,
                self::class
            ));
        }

        return $valor;
    }
}
