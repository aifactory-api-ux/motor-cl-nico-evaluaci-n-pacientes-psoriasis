<?php

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Respuesta de resolución de una evaluación de elegibilidad.
 *
 * Replica la interfaz TypeScript EvaluacionResolutionResponse: agrupa la
 * evaluación, la ficha del paciente, el resultado calculado y el desglose de
 * criterios aplicados. El contrato OpenAPI exige que cada criterio incluya
 * el detalle de la regla (codigo, nombre y tipo).
 */
final class EvaluacionResolutionResponse
{
    public EvaluacionDTO $evaluacion;

    public PacienteDTO $paciente;

    public ResultadoEvaluacionDTO $resultado;

    /**
     * Criterios evaluados con su estado y el detalle de la regla aplicada.
     *
     * @var list<EvaluacionCriterioDTO>
     */
    public array $criterios;

    /**
     * @param  list<EvaluacionCriterioDTO>  $criterios
     */
    public function __construct(
        EvaluacionDTO $evaluacion,
        PacienteDTO $paciente,
        ResultadoEvaluacionDTO $resultado,
        array $criterios = []
    ) {
        $this->evaluacion = $evaluacion;
        $this->paciente = $paciente;
        $this->resultado = $resultado;
        $this->criterios = self::normalizarCriterios($criterios);
    }

    /**
     * Construye la respuesta a partir de un payload con los nombres de campo
     * del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        $criterios = self::campo($datos, 'criterios');

        if (! is_array($criterios)) {
            throw new InvalidArgumentException(sprintf(
                'La sección "criterios" de %s debe ser una lista.',
                self::class
            ));
        }

        $mapeados = [];

        foreach ($criterios as $criterio) {
            if ($criterio instanceof EvaluacionCriterioDTO) {
                $mapeados[] = $criterio;

                continue;
            }

            if (! is_array($criterio)) {
                throw new InvalidArgumentException(sprintf(
                    'Cada criterio de %s debe ser un objeto o un %s.',
                    self::class,
                    EvaluacionCriterioDTO::class
                ));
            }

            $mapeados[] = EvaluacionCriterioDTO::fromArray($criterio);
        }

        return new self(
            evaluacion: EvaluacionDTO::fromArray(self::seccion($datos, 'evaluacion')),
            paciente: PacienteDTO::fromArray(self::seccion($datos, 'paciente')),
            resultado: ResultadoEvaluacionDTO::fromArray(self::seccion($datos, 'resultado')),
            criterios: $mapeados
        );
    }

    /**
     * Serializa la respuesta con los nombres de campo del contrato de la API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'evaluacion' => $this->evaluacion->toArray(),
            'paciente' => $this->paciente->toArray(),
            'resultado' => $this->resultado->toArray(),
            'criterios' => array_map(
                static fn (EvaluacionCriterioDTO $criterio): array => $criterio->toArray(),
                $this->criterios
            ),
        ];
    }

    /**
     * Obtiene una sección obligatoria del payload de origen.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private static function seccion(array $datos, string $clave): array
    {
        $seccion = self::campo($datos, $clave);

        if (! is_array($seccion)) {
            throw new InvalidArgumentException(sprintf(
                'La sección "%s" de %s debe ser un objeto.',
                $clave,
                self::class
            ));
        }

        return $seccion;
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
     * Valida que el desglose contenga únicamente instancias del DTO de
     * criterio y conserve el orden de evaluación.
     *
     * @param  array<int, mixed>  $criterios
     * @return list<EvaluacionCriterioDTO>
     */
    private static function normalizarCriterios(array $criterios): array
    {
        $normalizados = [];

        foreach ($criterios as $criterio) {
            if (! $criterio instanceof EvaluacionCriterioDTO) {
                throw new InvalidArgumentException(sprintf(
                    'Cada criterio de %s debe ser un %s.',
                    self::class,
                    EvaluacionCriterioDTO::class
                ));
            }

            $normalizados[] = $criterio;
        }

        return $normalizados;
    }
}
