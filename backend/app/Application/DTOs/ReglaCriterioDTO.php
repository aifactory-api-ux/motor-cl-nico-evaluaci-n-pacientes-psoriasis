<?php

namespace App\Application\DTOs;

use App\Domain\Entities\Enums\TipoCriterio;
use InvalidArgumentException;

/**
 * Contrato de datos de la regla de criterio entre el backend y el frontend.
 *
 * Los campos coinciden uno a uno con la interfaz TypeScript ReglaCriterioDTO.
 */
final class ReglaCriterioDTO
{
    public string $id;

    public string $codigo;

    public string $nombre;

    public string $tipo;

    public string $descripcion;

    public bool $activo;

    public function __construct(
        string $id,
        string $codigo,
        string $nombre,
        TipoCriterio|string $tipo,
        string $descripcion,
        bool $activo
    ) {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->nombre = $nombre;
        $this->tipo = self::normalizarTipo($tipo)->value;
        $this->descripcion = $descripcion;
        $this->activo = $activo;
    }

    /**
     * Construye el DTO a partir de una fila de base de datos o de un payload
     * asociativo con los nombres de campo del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        $tipo = self::campo($datos, 'tipo');

        return new self(
            id: (string) self::campo($datos, 'id'),
            codigo: (string) self::campo($datos, 'codigo'),
            nombre: (string) self::campo($datos, 'nombre'),
            tipo: $tipo instanceof TipoCriterio ? $tipo : (string) $tipo,
            descripcion: (string) self::campo($datos, 'descripcion'),
            activo: self::booleano(self::campo($datos, 'activo'))
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
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
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

    /**
     * Convierte el valor de la bandera persistida como tinyint a booleano.
     */
    private static function booleano(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        if (is_string($valor)) {
            return in_array(strtolower(trim($valor)), ['1', 'true', 't', 'yes', 'si'], true);
        }

        return (bool) $valor;
    }
}
