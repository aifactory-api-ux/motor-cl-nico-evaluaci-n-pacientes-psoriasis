<?php

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Contrato de datos de la evaluación clínica entre el backend y el frontend.
 *
 * Los campos coinciden uno a uno con la interfaz TypeScript EvaluacionDTO.
 */
final class EvaluacionDTO
{
    public string $id;

    public string $paciente_id;

    public string $usuario_id;

    public int $edad;

    public int $psoriasis_meses;

    public float $pasi;

    public float $bsa;

    public bool $historial_tb;

    public bool $embarazo;

    public int $uso_biologicos_meses;

    public int $uso_corticoides_topicos_dias;

    public string $evaluada_en;

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
        $this->id = $id;
        $this->paciente_id = $paciente_id;
        $this->usuario_id = $usuario_id;
        $this->edad = $edad;
        $this->psoriasis_meses = $psoriasis_meses;
        $this->pasi = $pasi;
        $this->bsa = $bsa;
        $this->historial_tb = $historial_tb;
        $this->embarazo = $embarazo;
        $this->uso_biologicos_meses = $uso_biologicos_meses;
        $this->uso_corticoides_topicos_dias = $uso_corticoides_topicos_dias;
        $this->evaluada_en = $evaluada_en;
    }

    /**
     * Construye el DTO a partir de una fila de base de datos o de un payload
     * asociativo con los nombres de campo del contrato.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            id: (string) self::campo($datos, 'id'),
            paciente_id: (string) self::campo($datos, 'paciente_id'),
            usuario_id: (string) self::campo($datos, 'usuario_id'),
            edad: (int) self::campo($datos, 'edad'),
            psoriasis_meses: (int) self::campo($datos, 'psoriasis_meses'),
            pasi: (float) self::campo($datos, 'pasi'),
            bsa: (float) self::campo($datos, 'bsa'),
            historial_tb: self::booleano(self::campo($datos, 'historial_tb')),
            embarazo: self::booleano(self::campo($datos, 'embarazo')),
            uso_biologicos_meses: (int) self::campo($datos, 'uso_biologicos_meses'),
            uso_corticoides_topicos_dias: (int) self::campo($datos, 'uso_corticoides_topicos_dias'),
            evaluada_en: (string) self::campo($datos, 'evaluada_en')
        );
    }

    /**
     * Serializa el DTO con los nombres de campo del contrato de la API.
     *
     * @return array<string, mixed>
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
     * Obtiene un campo obligatorio del payload de origen.
     *
     * @param  array<string, mixed>  $datos
     */
    private static function campo(array $datos, string $clave): mixed
    {
        if (! array_key_exists($clave, $datos) || $datos[$clave] === null) {
            throw new InvalidArgumentException(sprintf(
                'Falta el campo "%s" para construir %s.',
                $clave,
                self::class
            ));
        }

        return $datos[$clave];
    }

    /**
     * Convierte el valor de una bandera persistida como tinyint a booleano.
     */
    private static function booleano(mixed $valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        if (is_string($valor)) {
            return in_array(strtolower(trim($valor)), ['1', 'true', 't', 'yes', 'si'], true);
        }

        return (bool) $valor;
    }
}
