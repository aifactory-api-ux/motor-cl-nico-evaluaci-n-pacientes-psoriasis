<?php

namespace App\Application\DTOs;

use App\Domain\Entities\Enums\EstadoCriterio;
use App\Domain\Entities\Enums\TipoCriterio;
use InvalidArgumentException;

/**
 * Contrato de datos del criterio evaluado entre el backend y el frontend.
 *
 * Los campos base (id, evaluacion_id, criterio_id, estado y motivo) coinciden
 * uno a uno con la interfaz TypeScript EvaluacionCriterioDTO. Los campos
 * opcionales codigo, nombre y tipo replican el detalle que el contrato
 * OpenAPI exige en EvaluacionCriterioDTODetalle (codigo y nombre) y que la
 * interfaz TypeScript EvaluacionCriterioDTODetalle extiende sobre la base.
 */
final class EvaluacionCriterioDTO
{
    public string $id;

    public string $evaluacion_id;

    public string $criterio_id;

    public string $estado;

    public ?string $motivo;

    public ?string $codigo;

    public ?string $nombre;

    public ?string $tipo;

    public function __construct(
        string $id,
        string $evaluacion_id,
        string $criterio_id,
        EstadoCriterio|string $estado,
        ?string $motivo = null,
        ?string $codigo = null,
        ?string $nombre = null,
        TipoCriterio|string|null $tipo = null
    ) {
        $this->id = $id;
        $this->evaluacion_id = $evaluacion_id;
        $this->criterio_id = $criterio_id;
        $this->estado = self::normalizarEstado($estado)->value;
        $this->motivo = $motivo;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->tipo = $tipo === null ? null : self::normalizarTipo($tipo)->value;
    }

    /**
     * Construye el DTO a partir de una fila de base de datos o de un payload
     * asociativo con los nombres de campo del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        $estado = self::campo($datos, 'estado');
        $tipo = self::campoOpcional($datos, 'tipo');

        return new self(
            id: (string) self::campo($datos, 'id'),
            evaluacion_id: (string) self::campo($datos, 'evaluacion_id'),
            criterio_id: (string) self::campo($datos, 'criterio_id'),
            estado: $estado instanceof EstadoCriterio ? $estado : (string) $estado,
            motivo: self::textoOpcional(self::campoOpcional($datos, 'motivo')),
            codigo: self::textoOpcional(self::campoOpcional($datos, 'codigo')),
            nombre: self::textoOpcional(self::campoOpcional($datos, 'nombre')),
            tipo: $tipo instanceof TipoCriterio ? $tipo : self::textoOpcional($tipo)
        );
    }

    /**
     * Serializa el DTO con los nombres de campo del contrato de la API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'evaluacion_id' => $this->evaluacion_id,
            'criterio_id' => $this->criterio_id,
            'estado' => $this->estado,
            'motivo' => $this->motivo,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
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
     * Obtiene un campo opcional del payload de origen.
     *
     * @param  array<string, mixed>  $datos
     */
    private static function campoOpcional(array $datos, string $clave): mixed
    {
        return array_key_exists($clave, $datos) ? $datos[$clave] : null;
    }

    /**
     * Normaliza un valor de texto opcional: los valores vacíos se
     * normalizan a null para no perder la semántica de "sin detalle".
     */
    private static function textoOpcional(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        if (! is_scalar($valor)) {
            return null;
        }

        $texto = (string) $valor;

        return trim($texto) === '' ? null : $texto;
    }

    /**
     * Resuelve el estado del criterio a un valor admitido del dominio.
     */
    private static function normalizarEstado(EstadoCriterio|string $estado): EstadoCriterio
    {
        if ($estado instanceof EstadoCriterio) {
            return $estado;
        }

        $valor = EstadoCriterio::tryFrom($estado);

        if ($valor === null) {
            throw new InvalidArgumentException(sprintf(
                'El estado de criterio "%s" no es un valor admitido para %s.',
                $estado,
                self::class
            ));
        }

        return $valor;
    }

    /**
     * Resuelve el tipo de criterio a un valor admitido del dominio.
     */
    private static function normalizarTipo(TipoCriterio|string $tipo): TipoCriterio
    {
        if ($tipo instanceof TipoCriterio) {
            return $tipo;
        }

        $valor = TipoCriterio::tryFrom($tipo);

        if ($valor === null) {
            throw new InvalidArgumentException(sprintf(
                'El tipo de criterio "%s" no es un valor admitido para %s.',
                $tipo,
                self::class
            ));
        }

        return $valor;
    }
}
