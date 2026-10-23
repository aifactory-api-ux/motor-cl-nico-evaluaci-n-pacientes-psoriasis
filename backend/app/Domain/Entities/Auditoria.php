<?php

namespace App\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Entidad de dominio del registro de auditoría.
 *
 * Mapea la tabla AUDITORIA: deja constancia de quién realizó cada acción
 * sobre el motor y sobre qué evaluación, con la marca de tiempo del hecho.
 */
final class Auditoria
{
    /**
     * Acción registrada al crear una evaluación.
     */
    public const ACCION_CREAR_EVALUACION = 'crear_evaluacion';

    /**
     * Acción registrada al consultar la resolución de una evaluación.
     */
    public const ACCION_CONSULTAR_RESOLUCION = 'consultar_resolucion';

    /**
     * Acción registrada al exportar el registro de auditoría.
     */
    public const ACCION_EXPORTAR_AUDITORIA = 'exportar_auditoria';

    /**
     * Acción registrada al consultar las estadísticas de reclutamiento.
     */
    public const ACCION_CONSULTAR_ESTADISTICAS = 'consultar_estadisticas';

    /**
     * Acciones admitidas por la auditoría del motor.
     *
     * @var array<int, string>
     */
    public const ACCIONES = [
        self::ACCION_CREAR_EVALUACION,
        self::ACCION_CONSULTAR_RESOLUCION,
        self::ACCION_EXPORTAR_AUDITORIA,
        self::ACCION_CONSULTAR_ESTADISTICAS,
    ];

    /**
     * Formato de la marca de tiempo persistida en AUDITORIA.
     */
    public const FORMATO_FECHA = 'Y-m-d H:i:s';

    public readonly string $id;

    public readonly string $usuario_id;

    public readonly string $evaluacion_id;

    public readonly string $accion;

    public readonly string $timestamp;

    /**
     * Construye el registro de auditoría validando los invariantes de dominio.
     */
    public function __construct(
        string $id,
        string $usuario_id,
        string $evaluacion_id,
        string $accion,
        string $timestamp
    ) {
        $this->id = self::validarUuid($id, 'id');
        $this->usuario_id = self::validarUuid($usuario_id, 'usuario_id');
        $this->evaluacion_id = self::validarUuid($evaluacion_id, 'evaluacion_id');
        $this->accion = self::validarAccion($accion);
        $this->timestamp = self::validarFecha($timestamp, 'timestamp', self::FORMATO_FECHA);
    }

    /**
     * Crea un registro de auditoría con un identificador UUID v7 generado por
     * el dominio.
     *
     * Cuando no se indica la marca de tiempo se usa la hora actual en UTC.
     */
    public static function crear(
        string $usuario_id,
        string $evaluacion_id,
        string $accion,
        ?string $timestamp = null,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? self::nuevoId(),
            usuario_id: $usuario_id,
            evaluacion_id: $evaluacion_id,
            accion: $accion,
            timestamp: $timestamp ?? gmdate(self::FORMATO_FECHA)
        );
    }

    /**
     * Indica si el registro corresponde a la creación de una evaluación.
     */
    public function esCreacionEvaluacion(): bool
    {
        return $this->accion === self::ACCION_CREAR_EVALUACION;
    }

    /**
     * Serializa la entidad con los nombres de columna de AUDITORIA.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'evaluacion_id' => $this->evaluacion_id,
            'accion' => $this->accion,
            'timestamp' => $this->timestamp,
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
     * Valida que la acción pertenezca al catálogo de acciones auditadas.
     */
    private static function validarAccion(string $valor): string
    {
        $accion = trim($valor);

        if (! in_array($accion, self::ACCIONES, true)) {
            throw new InvalidArgumentException(sprintf(
                'La acción "%s" no es una acción admitida por la auditoría.',
                $accion
            ));
        }

        return $accion;
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
