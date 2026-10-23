<?php

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Contrato de datos del paciente entre el backend y el frontend.
 *
 * Los campos coinciden uno a uno con la interfaz TypeScript PacienteDTO.
 */
final class PacienteDTO
{
    public string $id;

    public string $numero_historia_clinica;

    public string $fecha_nacimiento;

    public string $genero;

    public string $ciudad;

    public function __construct(
        string $id,
        string $numero_historia_clinica,
        string $fecha_nacimiento,
        string $genero,
        string $ciudad
    ) {
        $this->id = $id;
        $this->numero_historia_clinica = $numero_historia_clinica;
        $this->fecha_nacimiento = $fecha_nacimiento;
        $this->genero = $genero;
        $this->ciudad = $ciudad;
    }

    /**
     * Construye el DTO a partir de una fila de base de datos o de un payload
     * asociativo con los nombres de campo del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            id: (string) self::campo($datos, 'id'),
            numero_historia_clinica: (string) self::campo($datos, 'numero_historia_clinica'),
            fecha_nacimiento: (string) self::campo($datos, 'fecha_nacimiento'),
            genero: (string) self::campo($datos, 'genero'),
            ciudad: (string) self::campo($datos, 'ciudad')
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
            'numero_historia_clinica' => $this->numero_historia_clinica,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
            'ciudad' => $this->ciudad,
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
}
