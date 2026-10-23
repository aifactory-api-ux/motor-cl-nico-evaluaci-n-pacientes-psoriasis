<?php

namespace App\Domain\Entities\Enums;

/**
 * Tipo de regla del catálogo de criterios del protocolo ALM-PSO-2026.
 *
 * Los valores persistidos en REGLA_CRITERIO.tipo son: inclusion, exclusion.
 */
enum TipoCriterio: string
{
    case INCLUSION = 'inclusion';
    case EXCLUSION = 'exclusion';

    /**
     * Etiqueta legible del tipo para la interfaz clínica.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::INCLUSION => 'Inclusión',
            self::EXCLUSION => 'Exclusión',
        };
    }

    /**
     * Indica si el criterio debe cumplirse para ser elegible.
     */
    public function esInclusion(): bool
    {
        return $this === self::INCLUSION;
    }

    /**
     * Indica si el criterio bloquea la elegibilidad cuando se incumple.
     */
    public function esExclusion(): bool
    {
        return $this === self::EXCLUSION;
    }
}
