<?php

namespace App\Domain\Services;

use App\Domain\Entities\Evaluacion;
use App\Domain\Entities\EvaluacionCriterio;
use App\Domain\Entities\Enums\Resultado;
use App\Domain\Entities\ReglaCriterio;
use App\Domain\Entities\ResultadoEvaluacion;
use InvalidArgumentException;

/**
 * Motor de elegibilidad del protocolo ALM-PSO-2026.
 *
 * Servicio puro del dominio: recibe la fotografía clínica de una evaluación y
 * el catálogo de reglas de criterio, y devuelve la decisión de elegibilidad
 * con el detalle de trazabilidad que la sustenta. No depende del framework ni
 * de la infraestructura.
 *
 * Reglas de resolución:
 *  - Un criterio incumplido (fail) bloquea la elegibilidad: el resultado es
 *    No Elegible, provenga de un criterio de inclusión o de exclusión.
 *  - Un criterio no evaluable (na) no bloquea, pero deja la decisión
 *    pendiente: el resultado es Revisión.
 *  - Sin criterios incumplidos ni no evaluables, el resultado es Elegible.
 */
final class MotorElegibilidad
{
    /**
     * Orden canónico de los criterios del protocolo: primero los de inclusión
     * y después los de exclusión. Es el orden en que se presenta la
     * trazabilidad de cada decisión.
     *
     * @var array<int, string>
     */
    public const ORDEN_CRITERIOS = [
        EvaluadorCriterio::CODIGO_EDAD_MIN,
        EvaluadorCriterio::CODIGO_PSORIASIS_CRONICA,
        EvaluadorCriterio::CODIGO_PASI_MIN,
        EvaluadorCriterio::CODIGO_BSA_MIN,
        EvaluadorCriterio::CODIGO_TB_HISTORIAL,
        EvaluadorCriterio::CODIGO_EMBARAZO,
        EvaluadorCriterio::CODIGO_BIOLOGICOS_90D,
        EvaluadorCriterio::CODIGO_CORTICOIDES_14D,
    ];

    /**
     * Resuelve la elegibilidad de una evaluación y devuelve el resultado.
     *
     * @param  array<int, ReglaCriterio>  $criterios
     */
    public static function evaluar(Evaluacion $evaluacion, array $criterios): Resultado
    {
        $resolucion = self::resolver($evaluacion, $criterios);

        return $resolucion['resultado'];
    }

    /**
     * Resuelve la elegibilidad de una evaluación: resultado, desglose de
     * criterios y detalle de trazabilidad.
     *
     * @param  array<int, ReglaCriterio>  $criterios
     * @return array{resultado: Resultado, criterios: array<int, EvaluacionCriterio>, detalle_trazabilidad: string}
     */
    public static function resolver(Evaluacion $evaluacion, array $criterios): array
    {
        $reglas = self::reglasActivasOrdenadas($criterios);
        $evaluados = self::evaluarCriterios($evaluacion, $reglas);

        return [
            'resultado' => self::resolverResultado($reglas, $evaluados),
            'criterios' => $evaluados,
            'detalle_trazabilidad' => self::detalleTrazabilidad($reglas, $evaluados),
        ];
    }

    /**
     * Evalúa cada regla activa contra la evaluación y devuelve el desglose de
     * criterios en el orden del protocolo.
     *
     * @param  array<int, ReglaCriterio>  $reglas
     * @return array<int, EvaluacionCriterio>
     */
    public static function evaluarCriterios(Evaluacion $evaluacion, array $reglas): array
    {
        $evaluados = [];

        foreach (self::reglasActivasOrdenadas($reglas) as $regla) {
            $evaluados[] = EvaluadorCriterio::evaluar($regla, $evaluacion);
        }

        return $evaluados;
    }

    /**
     * Resuelve el resultado a partir del detalle de los criterios evaluados.
     *
     * @param  array<int, ReglaCriterio>  $reglas
     * @param  array<int, EvaluacionCriterio>  $evaluados
     */
    public static function resolverResultado(array $reglas, array $evaluados): Resultado
    {
        if ($reglas === [] || $evaluados === []) {
            return Resultado::REVISION;
        }

        $incumplidos = 0;
        $noEvaluables = 0;

        foreach ($evaluados as $evaluado) {
            if ($evaluado->estado->incumple()) {
                $incumplidos++;
                continue;
            }

            if (! $evaluado->estado->aplica()) {
                $noEvaluables++;
            }
        }

        if ($incumplidos > 0) {
            return Resultado::NO_ELEGIBLE;
        }

        if ($noEvaluables > 0) {
            return Resultado::REVISION;
        }

        return Resultado::ELEGIBLE;
    }

    /**
     * Construye el detalle de trazabilidad que acompaña a la decisión.
     *
     * El texto resume los criterios de inclusión cumplidos, los criterios de
     * inclusión no cumplidos y los criterios de exclusión que bloquearon la
     * elegibilidad.
     *
     * @param  array<int, ReglaCriterio>  $reglas
     * @param  array<int, EvaluacionCriterio>  $evaluados
     */
    public static function detalleTrazabilidad(array $reglas, array $evaluados): string
    {
        $reglasPorCriterio = [];

        foreach ($reglas as $regla) {
            $reglasPorCriterio[$regla->id] = $regla;
        }

        $inclusionTotal = 0;
        $inclusionCumplidos = 0;
        $exclusionTotal = 0;
        $fallosInclusion = [];
        $bloqueosExclusion = [];
        $noEvaluables = [];

        foreach ($evaluados as $evaluado) {
            $regla = $reglasPorCriterio[$evaluado->criterio_id] ?? null;
            $nombre = $regla instanceof ReglaCriterio ? $regla->nombre : $evaluado->criterio_id;
            $esInclusion = ! ($regla instanceof ReglaCriterio) || $regla->tipo->esInclusion();

            if ($esInclusion) {
                $inclusionTotal++;
            } else {
                $exclusionTotal++;
            }

            if ($evaluado->estado->esCumplido()) {
                if ($esInclusion) {
                    $inclusionCumplidos++;
                }

                continue;
            }

            if ($evaluado->estado->incumple()) {
                if ($esInclusion) {
                    $fallosInclusion[] = $nombre;
                } else {
                    $bloqueosExclusion[] = $nombre;
                }

                continue;
            }

            $noEvaluables[] = $nombre;
        }

        if ($fallosInclusion === []) {
            $lineaInclusion = sprintf(
                'Inclusión: %d de %d criterios cumplidos.',
                $inclusionCumplidos,
                $inclusionTotal
            );
        } else {
            $lineaInclusion = sprintf(
                'Inclusión: %d de %d criterios cumplidos. No se cumplieron: %s.',
                $inclusionCumplidos,
                $inclusionTotal,
                implode(', ', $fallosInclusion)
            );
        }

        if ($bloqueosExclusion === []) {
            $lineaExclusion = 'Exclusión: ningún criterio de exclusión bloqueó la elegibilidad.';
        } else {
            $lineaExclusion = sprintf(
                'Exclusión: %d de %d criterios de exclusión bloqueó la elegibilidad: %s.',
                count($bloqueosExclusion),
                $exclusionTotal,
                implode(', ', $bloqueosExclusion)
            );
        }

        $lineas = [$lineaInclusion, $lineaExclusion];

        if ($noEvaluables !== []) {
            $lineas[] = sprintf('No evaluables: %s.', implode(', ', $noEvaluables));
        }

        return implode("\n", $lineas);
    }

    /**
     * Crea el resultado persistible de una evaluación con su detalle de
     * trazabilidad.
     *
     * @param  array<int, ReglaCriterio>  $criterios
     */
    public static function crearResultado(
        Evaluacion $evaluacion,
        array $criterios,
        ?string $creadaEn = null
    ): ResultadoEvaluacion {
        $resolucion = self::resolver($evaluacion, $criterios);

        return ResultadoEvaluacion::crear(
            evaluacion_id: $evaluacion->id,
            resultado: $resolucion['resultado'],
            detalle_trazabilidad: $resolucion['detalle_trazabilidad'],
            creada_en: $creadaEn
        );
    }

    /**
     * Filtra las reglas inactivas y las ordena según el protocolo.
     *
     * Las reglas cuyo código no pertenece al catálogo canónico se conservan al
     * final, en el orden de entrega, para no perder trazabilidad.
     *
     * @param  array<int, ReglaCriterio>  $criterios
     * @return array<int, ReglaCriterio>
     */
    public static function reglasActivasOrdenadas(array $criterios): array
    {
        $activas = [];

        foreach ($criterios as $criterio) {
            if (! $criterio instanceof ReglaCriterio) {
                throw new InvalidArgumentException(
                    'El catálogo de criterios del motor solo admite reglas de criterio.'
                );
            }

            if ($criterio->activo) {
                $activas[$criterio->id] = $criterio;
            }
        }

        $ordenadas = [];

        foreach (self::ORDEN_CRITERIOS as $codigo) {
            foreach ($activas as $id => $regla) {
                if ($regla->codigo === $codigo) {
                    $ordenadas[] = $regla;
                    unset($activas[$id]);
                }
            }
        }

        foreach ($activas as $regla) {
            $ordenadas[] = $regla;
        }

        return $ordenadas;
    }
}
