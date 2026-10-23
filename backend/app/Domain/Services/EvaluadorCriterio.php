<?php

namespace App\Domain\Services;

use App\Domain\Entities\Evaluacion;
use App\Domain\Entities\EvaluacionCriterio;
use App\Domain\Entities\Enums\EstadoCriterio;
use App\Domain\Entities\ReglaCriterio;

/**
 * Evaluador de un criterio del protocolo contra la fotografía clínica de una
 * evaluación.
 *
 * Servicio puro del dominio: no depende del framework ni de infraestructura y
 * es la implementación autoritativa de los umbrales del protocolo ALM-PSO-2026.
 * El catálogo de reglas de REGLA_CRITERIO se siembra con estos mismos códigos
 * y con los umbrales que aquí se aplican.
 */
final class EvaluadorCriterio
{
    /**
     * Códigos de las reglas de criterio del protocolo ALM-PSO-2026.
     */
    public const CODIGO_EDAD_MIN = 'CRIT_EDAD_MIN';
    public const CODIGO_PSORIASIS_CRONICA = 'CRIT_PSORIASIS_CRONICA';
    public const CODIGO_PASI_MIN = 'CRIT_PASI_MIN';
    public const CODIGO_BSA_MIN = 'CRIT_BSA_MIN';
    public const CODIGO_TB_HISTORIAL = 'CRIT_TB_HISTORIAL';
    public const CODIGO_EMBARAZO = 'CRIT_EMBARAZO';
    public const CODIGO_BIOLOGICOS_90D = 'CRIT_BIOLOGICOS_90D';
    public const CODIGO_CORTICOIDES_14D = 'CRIT_CORTICOIDES_14D';

    /**
     * Umbrales del protocolo: edad mínima, evolución mínima de la psoriasis,
     * severidad mínima (PASI y BSA) y ventanas de exposición a fármacos.
     */
    public const EDAD_MINIMA = 18;
    public const PSORIASIS_MINIMA_MESES = 6;
    public const PASI_MINIMO = 10.0;
    public const BSA_MINIMO = 10.0;
    public const BIOLOGICOS_MAXIMOS_MESES = 3;
    public const CORTICOIDES_MAXIMOS_DIAS = 14;

    /**
     * Evalúa una regla contra la evaluación y devuelve el veredicto del
     * criterio con su motivo cuando no se cumple.
     */
    public static function evaluar(ReglaCriterio $criterio, Evaluacion $evaluacion): EvaluacionCriterio
    {
        $veredicto = self::veredicto($criterio->codigo, $evaluacion);

        return EvaluacionCriterio::crear(
            evaluacion_id: $evaluacion->id,
            criterio_id: $criterio->id,
            estado: $veredicto['estado'],
            motivo: $veredicto['motivo']
        );
    }

    /**
     * Aplica la regla asociada al código de la regla sobre los datos clínicos.
     *
     * Un código sin regla definida no bloquea la evaluación: se marca como no
     * aplicable para que la decisión quede pendiente de revisión manual.
     *
     * @return array{estado: EstadoCriterio, motivo: string|null}
     */
    private static function veredicto(string $codigo, Evaluacion $evaluacion): array
    {
        return match ($codigo) {
            self::CODIGO_EDAD_MIN => self::cumple(
                $evaluacion->edad >= self::EDAD_MINIMA,
                sprintf(
                    'Edad %d: se requiere una edad mayor o igual a %d.',
                    $evaluacion->edad,
                    self::EDAD_MINIMA
                )
            ),
            self::CODIGO_PSORIASIS_CRONICA => self::cumple(
                $evaluacion->psoriasis_meses >= self::PSORIASIS_MINIMA_MESES,
                sprintf(
                    'Psoriasis diagnosticada hace %d meses: se requieren al menos %d meses de evolución.',
                    $evaluacion->psoriasis_meses,
                    self::PSORIASIS_MINIMA_MESES
                )
            ),
            self::CODIGO_PASI_MIN => self::cumple(
                $evaluacion->pasi >= self::PASI_MINIMO,
                sprintf(
                    'PASI %.1f: se requiere un valor mayor o igual a %d.',
                    $evaluacion->pasi,
                    self::PASI_MINIMO
                )
            ),
            self::CODIGO_BSA_MIN => self::cumple(
                $evaluacion->bsa >= self::BSA_MINIMO,
                sprintf(
                    'BSA %.1f%%: se requiere un valor mayor o igual a %d%%.',
                    $evaluacion->bsa,
                    self::BSA_MINIMO
                )
            ),
            self::CODIGO_TB_HISTORIAL => self::cumple(
                ! $evaluacion->historial_tb,
                'El paciente presenta historial de tuberculosis.'
            ),
            self::CODIGO_EMBARAZO => self::cumple(
                ! $evaluacion->embarazo,
                'El paciente está embarazada.'
            ),
            self::CODIGO_BIOLOGICOS_90D => self::cumple(
                $evaluacion->uso_biologicos_meses < self::BIOLOGICOS_MAXIMOS_MESES,
                sprintf(
                    'Uso de biológicos en los últimos %d meses: no se admiten fármacos biológicos en los últimos 90 días.',
                    $evaluacion->uso_biologicos_meses
                )
            ),
            self::CODIGO_CORTICOIDES_14D => self::cumple(
                $evaluacion->uso_corticoides_topicos_dias < self::CORTICOIDES_MAXIMOS_DIAS,
                sprintf(
                    'Uso de corticoides tópicos en los últimos %d días: no se admiten corticoides en los últimos 14 días.',
                    $evaluacion->uso_corticoides_topicos_dias
                )
            ),
            default => [
                'estado' => EstadoCriterio::NA,
                'motivo' => sprintf(
                    'El criterio "%s" no tiene una regla de evaluación definida y requiere revisión manual.',
                    $codigo
                ),
            ],
        };
    }

    /**
     * Traduce el cumplimiento de un criterio al estado persistido.
     *
     * @return array{estado: EstadoCriterio, motivo: string|null}
     */
    private static function cumple(bool $cumple, string $motivoFallo): array
    {
        return [
            'estado' => $cumple ? EstadoCriterio::PASS : EstadoCriterio::FAIL,
            'motivo' => $cumple ? null : $motivoFallo,
        ];
    }
}
