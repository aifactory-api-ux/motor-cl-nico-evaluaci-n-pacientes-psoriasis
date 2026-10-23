<?php

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Contrato de datos del registro de auditoría entre el backend y el frontend.
 *
 * Los campos coinciden uno a uno con la interfaz TypeScript AuditoriaDTO.
 * Los valores admitidos en "accion" son los definidos en el contrato de
 * esquema del arquitecto para la tabla AUDITORIA.
 */
final class AuditoriaDTO
{
    /**
     * Acciones auditables del motor de pre-selección clínica.
     */
    public const ACCIONES = [
        'crear_evaluacion',
        'consultar_resolucion',
        'exportar_auditoria',
        'consultar_estadisticas',
    ];

    public string $id;

    public string $usuario_id;

    public string $evaluacion_id;

    public string $accion;

    public string $timestamp;

    public function __construct(
        string $id,
        string $usuario_id,
        string $evaluacion_id,
        string $accion,
        string $timestamp
    ) {
        $this->id = $id;
        $this->usuario_id = $usuario_id;
        $this->evaluacion_id = $evaluacion_id;
        $this->accion = self::normalizarAccion($accion);
        $this->timestamp = $timestamp;
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
            usuario_id: (string) self::campo($datos, 'usuario_id'),
            evaluacion_id: (string) self::campo($datos, 'evaluacion_id'),
            accion: (string) self::campo($datos, 'accion'),
            timestamp: (string) self::campo($datos, 'timestamp')
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
            'usuario_id' => $this->usuario_id,
            'evaluacion_id' => $this->evaluacion_id,
            'accion' => $this->accion,
            'timestamp' => $this->timestamp,
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
     * Verifica que la acción registrada sea una de las admitidas por el
     * contrato de auditoría del protocolo ALM-PSO-2026.
     */
    private static function normalizarAccion(string $accion): string
    {
        if (! in_array($accion, self::ACCIONES, true)) {
            throw new InvalidArgumentException(sprintf(
                'La acción "%s" no es un valor admitido para %s.',
                $accion,
                self::class
            ));
        }

        return $accion;
    }
}
