<?php

namespace App\Domain\Entities\Enums;

/**
 * Rol del usuario del protocolo ALM-PSO-2026.
 *
 * Los valores persistidos en USUARIO.rol son: investigador, gestor.
 */
enum Rol: string
{
    case INVESTIGADOR = 'investigador';
    case GESTOR = 'gestor';

    /**
     * Etiqueta legible del rol para la interfaz clínica.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::INVESTIGADOR => 'Investigador Clínico',
            self::GESTOR => 'Gestor del Ensayo',
        };
    }

    /**
     * Indica si el rol corresponde al investigador clínico.
     */
    public function esInvestigador(): bool
    {
        return $this === self::INVESTIGADOR;
    }

    /**
     * Indica si el rol corresponde al gestor del ensayo.
     */
    public function esGestor(): bool
    {
        return $this === self::GESTOR;
    }
}
