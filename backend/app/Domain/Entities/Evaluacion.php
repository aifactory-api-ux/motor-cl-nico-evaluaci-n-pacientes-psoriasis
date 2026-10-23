<?php

namespace App\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Entidad de dominio de la evaluación clínica de un paciente.
 *
 * Mapea la tabla EVALUACION: es la fotografía clínica que el motor de
 * elegibilidad contrasta con el catálogo de criterios del protocolo
 * ALM-PSO-2026.
 *
 * La entidad no aplica umbrales clínicos: un paciente menor de edad o con un
 * PASI bajo es una evaluación válida que el motor declara No Elegible, no un
 * dato inválido. Solo se validan los invariantes de forma (valores no
 * negativos e identificadores).
 */
final class Evaluacion
{
    /**
     * Formato de la marca de tiempo persistida en EVALUACION.
     */
    public const FORMATO_FECHA_EVALUACION = 'Y-m-d H:i:s';

    public readonly string $id;

    public readonly string $paciente_id;

    public readonly string $usuario_id;

    public readonly int $edad;

    public readonly int $psoriasis_meses;

    public readonly float $pasi;

    public readonly float $bsa;

    public readonly bool $historial_tb;

    public readonly bool $embarazo;

    public readonly int $uso_biologicos_meses;

    public readonly int $uso_corticoides_topicos_dias;

    public readonly string $evaluada_en;

    /**
     * Construye la evaluación validando los invariantes de forma.
     */
    public function __construct(
        string $id,
        string $paciente_id,
        string $usuario_id,
        int $edad,
        int $psoriasis_meses,
        float $pasi,
        float $bsa,
        bool $historial_tb,
        bool $embarazo,
        int $uso_biologicos_meses,
        int $uso_corticoides_topicos_dias,
        string $evaluada_en
    ) {
        $this->id = self::validarUuid($id, 'id');
        $this->paciente_id = self::validarUuid($paciente_id, 'paciente_id');
        $this->usuario_id = self::validarUuid($usuario_id, 'usuario_id');
        $this->edad = self::validarEnteroNoNegativo($edad, 'edad');
        $this->psoriasis_meses = self::validarEnteroNoNegativo($psoriasis_meses, 'psoriasis_meses');
        $this->pasi = self::validarDecimalNoNegativo($pasi, 'pasi');
        $this->bsa = self::validarDecimalNoNegativo($bsa, 'bsa');
        $this->historial_tb = $historial_tb;
        $this->embarazo = $embarazo;
        $this->uso_biologicos_meses = self::validarEnteroNoNegativo($uso_biologicos_meses, 'uso_biologicos_meses');
        $this->uso_corticoides_topicos_dias = self::validarEnteroNoNegativo(
            $uso_corticoides_topicos_dias,
            'uso_corticoides_topicos_dias'
        );
        $this->evaluada_en = self::validarFecha($evaluada_en, 'evaluada_en', self::FORMATO_FECHA_EVALUACION);
    }

    /**
     * Crea una evaluación con un identificador UUID v7 generado por el dominio.
     *
     * Cuando no se indica la marca de tiempo se usa la hora actual en UTC.
     */
    public static function crear(
        string $paciente_id,
        string $usuario_id,
        int $edad,
        int $psoriasis_meses,
        float $pasi,
        float $bsa,
        bool $historial_tb,
        bool $embarazo,
        int $uso_biologicos_meses,
        int $uso_corticoides_topicos_dias,
        ?string $evaluada_en = null,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? self::nuevoId(),
            paciente_id: $paciente_id,
            usuario_id: $usuario_id,
            edad: $edad,
            psoriasis_meses: $psoriasis_meses,
            pasi: $pasi,
            bsa: $bsa,
            historial_tb: $historial_tb,
            embarazo: $embarazo,
            uso_biologicos_meses: $uso_biologicos_meses,
            uso_corticoides_topicos_dias: $uso_corticoides_topicos_dias,
            evaluada_en: $evaluada_en ?? gmdate(self::FORMATO_FECHA_EVALUACION)
        );
    }

    /**
     * Serializa la entidad con los nombres de columna de EVALUACION.
     *
     * @return array<string, bool|float|int|string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'paciente_id' => $this->paciente_id,
            'usuario_id' => $this->usuario_id,
            'edad' => $this->edad,
            'psoriasis_meses' => $this->psoriasis_meses,
            'pasi' => $this->pasi,
            'bsa' => $this->bsa,
            'historial_tb' => $this->historial_tb,
            'embarazo' => $this->embarazo,
            'uso_biologicos_meses' => $this->uso_biologicos_meses,
            'uso_corticoides_topicos_dias' => $this->uso_corticoides_topicos_dias,
            'evaluada_en' => $this->evaluada_en,
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
     * Valida que un valor entero no sea negativo.
     */
    private static function validarEnteroNoNegativo(int $valor, string $campo): int
    {
        if ($valor < 0) {
            throw new InvalidArgumentException(sprintf(
                'El campo "%s" no puede ser negativo.',
                $campo
            ));
        }

        return $valor;
    }

    /**
     * Valida que un valor decimal no sea negativo.
     */
    private static function validarDecimalNoNegativo(float $valor, string $campo): float
    {
        if ($valor < 0.0) {
            throw new InvalidArgumentException(sprintf(
                'El campo "%s" no puede ser negativo.',
                $campo
            ));
        }

        return $valor;
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
