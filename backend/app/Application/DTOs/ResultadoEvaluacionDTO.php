<?php

namespace App\Application\DTOs;

use App\Domain\Entities\Enums\Resultado;
use InvalidArgumentException;

/**
 * Contrato de datos del resultado de la evaluación entre el backend y el
 * frontend.
 *
 * Los campos coinciden uno a uno con la interfaz TypeScript
 * ResultadoEvaluacionDTO.
 */
final class ResultadoEvaluacionDTO
{
    public string $id;

    public string $evaluacion_id;

    public string $resultado;

    public string $detalle_trazabilidad;

    public string $creada_en;

    public function __construct(
        string $id,
        string $evaluacion_id,
        Resultado|string $resultado,
        string $detalle_trazabilidad,
        string $creada_en
    ) {
        $this->id = $id;
        $this->evaluacion_id = $evaluacion_id;
        $this->resultado = self::normalizarResultado($resultado)->value;
        $this->detalle_trazabilidad = $detalle_trazabilidad;
        $this->creada_en = $creada_en;
    }

    /**
     * Construye el DTO a partir de una fila de base de datos o de un payload
     * asociativo con los nombres de campo del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        $resultado = self::campo($datos, 'resultado');

        return new self(
            id: (string) self::campo($datos, 'id'),
            evaluacion_id: (string) self::campo($datos, 'evaluacion_id'),
            resultado: $resultado instanceof Resultado ? $resultado : (string) $resultado,
            detalle_trazabilidad: (string) self::campo($datos, 'detalle_trazabilidad'),
            creada_en: (string) self::campo($datos, 'creada_en')
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
            'evaluacion_id' => $this->evaluacion_id,
            'resultado' => $this->resultado,
            'detalle_trazabilidad' => $this->detalle_trazabilidad,
            'creada_en' => $this->creada_en,
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
     * Resuelve el resultado a un valor admitido del dominio.
     */
    private static function normalizarResultado(Resultado|string $resultado): Resultado
    {
        if ($resultado instanceof Resultado) {
            return $resultado;
        }

        $valor = Resultado::tryFrom($resultado);

        if ($valor === null) {
            throw new InvalidArgumentException(sprintf(
                'El resultado "%s" no es un valor admitido para %s.',
                $resultado,
                self::class
            ));
        }

        return $valor;
    }
}
