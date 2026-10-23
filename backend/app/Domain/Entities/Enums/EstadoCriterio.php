<?php

namespace App\Domain\Entities\Enums;

/**
 * Estado de un criterio de evaluación tras aplicar su regla.
 *
 * Los valores persistidos en EVALUACION_CRITERIO.estado son: pass, fail, na.
 */
enum EstadoCriterio: string
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case NA = 'na';

    /**
     * Etiqueta legible del estado para la interfaz clínica.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::PASS => 'Cumplido',
            self::FAIL => 'No Cumplido',
            self::NA => 'No Aplica',
        };
    }

    /**
     * Indica si el criterio se cumple.
     */
    public function esCumplido(): bool
    {
        return $this === self::PASS;
    }

    /**
     * Indica si el criterio no se cumple y bloquea la elegibilidad.
     */
    public function incumple(): bool
    {
        return $this === self::FAIL;
    }

    /**
     * Indica si el criterio aplica a la evaluación.
     */
    public function aplica(): bool
    {
        return $this !== self::NA;
    }
}
