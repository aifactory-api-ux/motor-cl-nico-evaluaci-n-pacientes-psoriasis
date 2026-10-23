<?php

namespace App\Domain\Entities\Enums;

/**
 * Resultado de la evaluación de elegibilidad del protocolo ALM-PSO-2026.
 *
 * Los valores persistidos en RESULTADO_EVALUACION.resultado son: elegible,
 * no_elegible, revision.
 */
enum Resultado: string
{
    case ELEGIBLE = 'elegible';
    case NO_ELEGIBLE = 'no_elegible';
    case REVISION = 'revision';

    /**
     * Etiqueta legible del resultado para la interfaz clínica.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::ELEGIBLE => 'Elegible',
            self::NO_ELEGIBLE => 'No Elegible',
            self::REVISION => 'Revisión',
        };
    }

    /**
     * Indica si el paciente cumple todos los criterios de inclusión y ninguno
     * de exclusión.
     */
    public function esElegible(): bool
    {
        return $this === self::ELEGIBLE;
    }

    /**
     * Indica si algún criterio bloqueó la elegibilidad del paciente.
     */
    public function esNoElegible(): bool
    {
        return $this === self::NO_ELEGIBLE;
    }

    /**
     * Indica si la decisión queda pendiente de revisión manual.
     */
    public function requiereRevision(): bool
    {
        return $this === self::REVISION;
    }
}
