<?php

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Métricas de reclutamiento del protocolo ALM-PSO-2026.
 *
 * Replica la interfaz TypeScript EstadisticasReclutamientoDTO: totales de
 * evaluaciones, tasa de elegibilidad, distribución de motivos de exclusión y
 * serie temporal de evaluaciones.
 */
final class EstadisticasReclutamientoDTO
{
    public int $total_evaluaciones;

    public int $elegibles;

    public int $no_elegibles;

    public int $revision;

    public float $tasa_elegibilidad;

    /**
     * Motivos de exclusión con la cantidad de evaluaciones que losocommerce.
     *
     * @var list<array{motivo: string, cantidad: int}>
     */
    public array $distribucion_motivos_exclusion;

    /**
     * Serie temporal de evaluaciones por fecha.
     *
     * @var list<array{fecha: string, evaluaciones: int, elegibles: int}>
     */
    public array $serie_temporal;

    /**
     * @param  list<array<string, mixed>>  $distribucion_motivos_exclusion
     * @param  list<array<string, mixed>>  $serie_temporal
     */
    public function __construct(
        int $total_evaluaciones,
        int $elegibles,
        int $no_elegibles,
        int $revision,
        float $tasa_elegibilidad,
        array $distribucion_motivos_exclusion = [],
        array $serie_temporal = []
    ) {
        $this->total_evaluaciones = $total_evaluaciones;
        $this->elegibles = $elegibles;
        $this->no_elegibles = $no_elegibles;
        $this->revision = $revision;
        $this->tasa_elegibilidad = $tasa_elegibilidad;
        $this->distribucion_motivos_exclusion = self::normalizarDistribucion($distribucion_motivos_exclusion);
        $this->serie_temporal = self::normalizarSerieTemporal($serie_temporal);
    }

    /**
     * Construye las estadísticas a partir de un payload con los nombres de
     * campo del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            total_evaluaciones: (int) self::campo($datos, 'total_evaluaciones'),
            elegibles: (int) self::campo($datos, 'elegibles'),
            no_elegibles: (int) self::campo($datos, 'no_elegibles'),
            revision: (int) self::campo($datos, 'revision'),
            tasa_elegibilidad: (float) self::campo($datos, 'tasa_elegibilidad'),
            distribucion_motivos_exclusion: self::lista($datos, 'distribucion_motivos_exclusion'),
            serie_temporal: self::lista($datos, 'serie_temporal')
        );
    }

    /**
     * Serializa las estadísticas con los nombres de campo del contrato de la
     * API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_evaluaciones' => $this->total_evaluaciones,
            'elegibles' => $this->elegibles,
            'no_elegibles' => $this->no_elegibles,
            'revision' => $this->revision,
            'tasa_elegibilidad' => $this->tasa_elegibilidad,
            'distribucion_motivos_exclusion' => $this->distribucion_motivos_exclusion,
            'serie_temporal' => $this->serie_temporal,
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
     * Obtiene una lista opcional del payload de origen.
     *
     * @param  array<string, mixed>  $datos
     * @return array<int, mixed>
     */
    private static function lista(array $datos, string $clave): array
    {
        if (! array_key_exists($clave, $datos) || $datos[$clave] === null) {
            return [];
        }

        if (! is_array($datos[$clave])) {
            throw new InvalidArgumentException(sprintf(
                'La sección "%s" de %s debe ser una lista.',
                $clave,
                self::class
            ));
        }

        return $datos[$clave];
    }

    /**
     * Normaliza la distribución de motivos de exclusión.
     *
     * @param  array<int, mixed>  $distribucion
     * @return list<array{motivo: string, cantidad: int}>
     */
    private static function normalizarDistribucion(array $distribucion): array
    {
        $normalizada = [];

        foreach ($distribucion as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException(sprintf(
                    'Cada motivo de exclusión de %s debe ser un objeto.',
                    self::class
                ));
            }

            $normalizada[] = [
                'motivo' => (string) self::campo($item, 'motivo'),
                'cantidad' => (int) self::campo($item, 'cantidad'),
            ];
        }

        return $normalizada;
    }

    /**
     * Normaliza la serie temporal de evaluaciones.
     *
     * @param  array<int, mixed>  $serie
     * @return list<array{fecha: string, evaluaciones: int, elegibles: int}>
     */
    private static function normalizarSerieTemporal(array $serie): array
    {
        $normalizada = [];

        foreach ($serie as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException(sprintf(
                    'Cada punto de la serie temporal de %s debe ser un objeto.',
                    self::class
                ));
            }

            $normalizada[] = [
                'fecha' => (string) self::campo($item, 'fecha'),
                'evaluaciones' => (int) self::campo($item, 'evaluaciones'),
                'elegibles' => (int) self::campo($item, 'elegibles'),
            ];
        }

        return $normalizada;
    }
}
