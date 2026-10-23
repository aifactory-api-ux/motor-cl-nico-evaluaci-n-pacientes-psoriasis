<?php

namespace App\Domain\Entities;

use App\Domain\Entities\Enums\Resultado;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Entidad de dominio del resultado de la evaluación de elegibilidad.
 *
 * Mapea la tabla RESULTADO_EVALUACION: cada evaluación produce exactamente un
 * resultado con la decisión final y el detalle de trazabilidad de los criterios
 * evaluados.
 */
final class ResultadoEvaluacion
{
    /**
     * Formato de la marca de tiempo persistida en RESULTADO_EVALUACION.
     */
    public const FORMATO_FECHA_CREACION = 'Y-m-d H:i:s';

    public readonly string $id;

    public readonly string $evaluacion_id;

    public readonly Resultado $resultado;

    public readonly string $detalle_trazabilidad;

    public readonly string $creada_en;

    /**
     * Construye el resultado validando los invariantes de dominio.
     */
    public function __construct(
        string $id,
        string $evaluacion_id,
        Resultado $resultado,
        string $detalle_trazabilidad,
        string $creada_en
    ) {
        $this->id = self::validarUuid($id, 'id');
        $this->evaluacion_id = self::validarUuid($evaluacion_id, 'evaluacion_id');
        $this->resultado = $resultado;
        $this->detalle_trazabilidad = self::validarDetalle($detalle_trazabilidad);
        $this->creada_en = self::validarFecha($creada_en, 'creada_en', self::FORMATO_FECHA_CREACION);
    }

    /**
     * Crea un resultado con un identificador UUID v7 generado por el dominio.
     *
     * Cuando no se indica la marca de tiempo se usa la hora actual en UTC.
     */
    public static function crear(
        string $evaluacion_id,
        Resultado $resultado,
        string $detalle_trazabilidad,
        ?string $creada_en = null,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? self::nuevoId(),
            evaluacion_id: $evaluacion_id,
            resultado: $resultado,
            detalle_trazabilidad: $detalle_trazabilidad,
            creada_en: $creada_en ?? gmdate(self::FORMATO_FECHA_CREACION)
        );
    }

    /**
     * Indica si la decisión fue elegible.
     */
    public function esElegible(): bool
    {
        return $this->resultado->esElegible();
    }

    /**
     * Indica si algún criterio bloqueó la elegibilidad.
     */
    public function esNoElegible(): bool
    {
        return $this->resultado->esNoElegible();
    }

    /**
     * Indica si la decisión queda pendiente de revisión manual.
     */
    public function requiereRevision(): bool
    {
        return $this->resultado->requiereRevision();
    }

    /**
     * Serializa la entidad con los nombres de columna de RESULTADO_EVALUACION.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'evaluacion_id' => $this->evaluacion_id,
            'resultado' => $this->resultado->value,
            'detalle_trazabilidad' => $this->detalle_trazabilidad,
            'creada_en' => $this->creada_en,
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
     * Valida que el detalle de trazabilidad informe la decisión tomada.
     */
    private static function validarDetalle(string $valor): string
    {
        $detalle = trim($valor);

        if ($detalle === '') {
            throw new InvalidArgumentException(
                'El detalle de trazabilidad es obligatorio: debe explicar la decisión del motor.'
            );
        }

        return $detalle;
    }

    /**
     * Valida que una marca de tiempo tenga el formato esperado.
     */
    private static function validarFecha(string $valor, string $campo, string $formato): string
    {
        $fecha = trim($valor);
        $objetoFecha = DateTimeImmutable::createFromFormat('!'.$formato, $fecha);

        if ($objetoFecha === false || $objetoFecha->format($formato) !== $fecha) {
            throw new InvalidArgumentException(sprintf(
                'El campo "%s" debe tener el formato %s.',
                $campo,
                $formato
            ));
        }

        return $fecha;
    }
}
