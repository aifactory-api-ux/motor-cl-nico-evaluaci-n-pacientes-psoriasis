<?php

namespace App\Domain\Entities;

use App\Domain\Entities\Enums\TipoCriterio;
use InvalidArgumentException;

/**
 * Entidad de dominio de una regla del catálogo de criterios.
 *
 * Mapea la tabla REGLA_CRITERIO: cada regla describe un criterio de inclusión
 * o de exclusión del protocolo ALM-PSO-2026 con un código estable que el motor
 * de elegibilidad usa para decidir cómo aplicarla.
 */
final class ReglaCriterio
{
    /**
     * Longitudes máximas de los campos de texto según el esquema.
     */
    private const LONGITUD_CODIGO = 64;
    private const LONGITUD_NOMBRE = 128;
    private const LONGITUD_DESCRIPCION = 255;

    public readonly string $id;

    public readonly string $codigo;

    public readonly string $nombre;

    public readonly TipoCriterio $tipo;

    public readonly string $descripcion;

    public readonly bool $activo;

    /**
     * Construye la regla validando los invariantes de dominio.
     */
    public function __construct(
        string $id,
        string $codigo,
        string $nombre,
        TipoCriterio $tipo,
        string $descripcion,
        bool $activo = true
    ) {
        $this->id = self::validarUuid($id, 'id');
        $this->codigo = self::validarTexto($codigo, 'codigo', self::LONGITUD_CODIGO);
        $this->nombre = self::validarTexto($nombre, 'nombre', self::LONGITUD_NOMBRE);
        $this->tipo = $tipo;
        $this->descripcion = self::validarTexto($descripcion, 'descripcion', self::LONGITUD_DESCRIPCION);
        $this->activo = $activo;
    }

    /**
     * Crea una regla con un identificador UUID v7 generado por el dominio.
     */
    public static function crear(
        string $codigo,
        string $nombre,
        TipoCriterio $tipo,
        string $descripcion,
        bool $activo = true,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? self::nuevoId(),
            codigo: $codigo,
            nombre: $nombre,
            tipo: $tipo,
            descripcion: $descripcion,
            activo: $activo
        );
    }

    /**
     * Indica si la regla debe cumplirse para que el paciente sea elegible.
     */
    public function esInclusion(): bool
    {
        return $this->tipo->esInclusion();
    }

    /**
     * Indica si la regla bloquea la elegibilidad cuando se incumple.
     */
    public function esExclusion(): bool
    {
        return $this->tipo->esExclusion();
    }

    /**
     * Serializa la entidad con los nombres de columna de REGLA_CRITERIO.
     *
     * @return array<string, bool|string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo->value,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];
    }

    /**
     * Genera un identificador UUID v7 (NFR012).
     *
     * Los 48 bits superiores contienen la marca de tiempo en milisegundos, el
     * byte 6 fija la versión 7 y el byte 8 fija la variante de la RFC 4122.
     */
    private static function nuevoId(): string
    {
        $bytes = random_bytes(16);
        $marcaTiempo = (int) floor(microtime(true) * 1000);

        $bytes[0] = chr(($marcaTiempo >> 40) & 0xFF);
        $bytes[1] = chr(($marcaTiempo >> 32) & 0xFF);
        $bytes[2] = chr(($marcaTiempo >> 24) & 0xFF);
        $bytes[3] = chr(($marcaTiempo >> 16) & 0xFF);
        $bytes[4] = chr(($marcaTiempo >> 8) & 0xFF);
        $bytes[5] = chr($marcaTiempo & 0xFF);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Valida que un identificador sea un UUID con formato canónico.
     */
    private static function validarUuid(string $valor, string $campo): string
    {
        $uuid = trim($valor);

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'El campo "%s" debe ser un UUID válido.',
                $campo
            ));
        }

        return strtolower($uuid);
    }

    /**
     * Valida un campo de texto obligatorio y su longitud máxima.
     */
    private static function validarTexto(string $valor, string $campo, int $longitudMaxima): string
    {
        $texto = trim($valor);

        if ($texto === '') {
            throw new InvalidArgumentException(sprintf(
                'El campo "%s" es obligatorio.',
                $campo
            ));
        }

        if (mb_strlen($texto) > $longitudMaxima) {
            throw new InvalidArgumentException(sprintf(
                'El campo "%s" no puede superar los %d caracteres.',
                $campo,
                $longitudMaxima
            ));
        }

        return $texto;
    }
}
