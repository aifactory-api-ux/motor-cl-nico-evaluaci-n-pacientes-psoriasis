<?php

namespace App\Domain\Entities;

use App\Domain\Entities\Enums\EstadoCriterio;
use InvalidArgumentException;

/**
 * Entidad de dominio del resultado de aplicar un criterio a una evaluación.
 *
 * Mapea la tabla EVALUACION_CRITERIO: cada fila deja constancia del estado
 * (pass, fail, na) de una regla sobre una evaluación y del motivo que explica
 * el incumplimiento.
 */
final class EvaluacionCriterio
{
    public readonly string $id;

    public readonly string $evaluacion_id;

    public readonly string $criterio_id;

    public readonly EstadoCriterio $estado;

    public readonly ?string $motivo;

    /**
     * Construye el criterio evaluado validando los invariantes de dominio.
     *
     * Un criterio incumplido exige un motivo que explique el descarte; un
     * criterio cumplido o no aplicable se registra sin motivo.
     */
    public function __construct(
        string $id,
        string $evaluacion_id,
        string $criterio_id,
        EstadoCriterio $estado,
        ?string $motivo = null
    ) {
        $this->id = self::validarUuid($id, 'id');
        $this->evaluacion_id = self::validarUuid($evaluacion_id, 'evaluacion_id');
        $this->criterio_id = self::validarUuid($criterio_id, 'criterio_id');
        $this->estado = $estado;
        $this->motivo = self::validarMotivo($motivo, $estado);
    }

    /**
     * Crea un criterio evaluado con un identificador UUID v7 generado por el
     * dominio.
     */
    public static function crear(
        string $evaluacion_id,
        string $criterio_id,
        EstadoCriterio $estado,
        ?string $motivo = null,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? self::nuevoId(),
            evaluacion_id: $evaluacion_id,
            criterio_id: $criterio_id,
            estado: $estado,
            motivo: $motivo
        );
    }

    /**
     * Indica si el criterio se cumple.
     */
    public function esCumplido(): bool
    {
        return $this->estado->esCumplido();
    }

    /**
     * Indica si el criterio incumple y bloquea la elegibilidad.
     */
    public function incumple(): bool
    {
        return $this->estado->incumple();
    }

    /**
     * Indica si el criterio aplica a la evaluación.
     */
    public function aplica(): bool
    {
        return $this->estado->aplica();
    }

    /**
     * Serializa la entidad con los nombres de columna de EVALUACION_CRITERIO.
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'evaluacion_id' => $this->evaluacion_id,
            'criterio_id' => $this->criterio_id,
            'estado' => $this->estado->value,
            'motivo' => $this->motivo,
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
     * Valida el motivo del veredicto: obligatorio cuando el criterio incumple
     * y ausente cuando el criterio se cumple.
     */
    private static function validarMotivo(?string $valor, EstadoCriterio $estado): ?string
    {
        $motivo = $valor === null ? null : trim($valor);

        if ($estado->incumple()) {
            if ($motivo === null || $motivo === '') {
                throw new InvalidArgumentException(
                    'Un criterio no cumplido exige un motivo que explique el descarte.'
                );
            }

            return $motivo;
        }

        if ($estado->esCumplido() && $motivo !== null && $motivo !== '') {
            throw new InvalidArgumentException(
                'Un criterio cumplido se registra sin motivo.'
            );
        }

        return $motivo === '' ? null : $motivo;
    }
}
