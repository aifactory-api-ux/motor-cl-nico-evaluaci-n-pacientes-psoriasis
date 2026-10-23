<?php

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Solicitud de creación de una evaluación clínica.
 *
 * Replica la interfaz TypeScript CrearEvaluacionRequest: agrupa los datos
 * demográficos del paciente y los parámetros clínicos de la evaluación.
 * El DTO valida presencia y tipo de los campos, pero no aplica rangos
 * clínicos: el protocolo ALM-PSO-2026 permite evaluar también los perfiles
 * sintéticos descartados para comprobar el trazabilidad del motor.
 */
final class CrearEvaluacionRequest
{
    /**
     * Datos demográficos del paciente.
     *
     * @var array{numero_historia_clinica: string, fecha_nacimiento: string, genero: string, ciudad: string}
     */
    public array $paciente;

    /**
     * Parámetros clínicos de la evaluación.
     *
     * @var array{edad: int, psoriasis_meses: int, pasi: float, bsa: float, historial_tb: bool, embarazo: bool, uso_biologicos_meses: int, uso_corticoides_topicos_dias: int}
     */
    public array $evaluacion;

    /**
     * @param  array<string, mixed>  $paciente
     * @param  array<string, mixed>  $evaluacion
     */
    public function __construct(array $paciente, array $evaluacion)
    {
        $this->paciente = self::normalizarPaciente($paciente);
        $this->evaluacion = self::normalizarEvaluacion($evaluacion);
    }

    /**
     * Construye la solicitud a partir del cuerpo de la petición.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            paciente: self::seccion($datos, 'paciente'),
            evaluacion: self::seccion($datos, 'evaluacion')
        );
    }

    /**
     * Serializa la solicitud con los nombres de campo del contrato de la API.
     *
     * @return array{paciente: array<string, mixed>, evaluacion: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'paciente' => $this->paciente,
            'evaluacion' => $this->evaluacion,
        ];
    }

    /**
     * Número de historia clínica del paciente.
     */
    public function numeroHistoriaClinica(): string
    {
        return $this->paciente['numero_historia_clinica'];
    }

    /**
     * Fecha de nacimiento del paciente en formato ISO (AAAA-MM-DD).
     */
    public function fechaNacimiento(): string
    {
        return $this->paciente['fecha_nacimiento'];
    }

    /**
     * Género declarado del paciente.
     */
    public function genero(): string
    {
        return $this->paciente['genero'];
    }

    /**
     * Ciudad de residencia del paciente.
     */
    public function ciudad(): string
    {
        return $this->paciente['ciudad'];
    }

    /**
     * Edad declarada del paciente en años.
     */
    public function edad(): int
    {
        return $this->evaluacion['edad'];
    }

    /**
     * Meses de psoriasis diagnosticados.
     */
    public function psoriasisMeses(): int
    {
        return $this->evaluacion['psoriasis_meses'];
    }

    /**
     * Índice PASI declarado por el investigador.
     */
    public function pasi(): float
    {
        return $this->evaluacion['pasi'];
    }

    /**
     * Porcentaje de superficie corporal afectada (BSA).
     */
    public function bsa(): float
    {
        return $this->evaluacion['bsa'];
    }

    /**
     * Indica si el paciente tiene historial de tuberculosis.
     */
    public function historialTb(): bool
    {
        return $this->evaluacion['historial_tb'];
    }

    /**
     * Indica si la paciente está embarazada.
     */
    public function embarazo(): bool
    {
        return $this->evaluacion['embarazo'];
    }

    /**
     * Meses de uso de biológicos antes de la evaluación.
     */
    public function usoBiologicosMeses(): int
    {
        return $this->evaluacion['uso_biologicos_meses'];
    }

    /**
     * Días de uso de corticoides tópicos antes de la evaluación.
     */
    public function usoCorticoidesTopicosDias(): int
    {
        return $this->evaluacion['uso_corticoides_topicos_dias'];
    }

    /**
     * Obtiene una sección obligatoria del cuerpo de la petición.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private static function seccion(array $datos, string $clave): array
    {
        $seccion = self::campo($datos, $clave);

        if (! is_array($seccion)) {
            throw new InvalidArgumentException(sprintf(
                'La sección "%s" de %s debe ser un objeto.',
                $clave,
                self::class
            ));
        }

        return $seccion;
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
     * Normaliza los datos demográficos del paciente.
     *
     * @param  array<string, mixed>  $paciente
     * @return array{numero_historia_clinica: string, fecha_nacimiento: string, genero: string, ciudad: string}
     */
    private static function normalizarPaciente(array $paciente): array
    {
        return [
            'numero_historia_clinica' => (string) self::campo($paciente, 'numero_historia_clinica'),
            'fecha_nacimiento' => (string) self::campo($paciente, 'fecha_nacimiento'),
            'genero' => (string) self::campo($paciente, 'genero'),
            'ciudad' => (string) self::campo($paciente, 'ciudad'),
        ];
    }

    /**
     * Normaliza los parámetros clínicos de la evaluación.
     *
     * @param  array<string, mixed>  $evaluacion
     * @return array{edad: int, psoriasis_meses: int, pasi: float, bsa: float, historial_tb: bool, embarazo: bool, uso_biologicos_meses: int, uso_corticoides_topicos_dias: int}
     */
    private static function normalizarEvaluacion(array $evaluacion): array
    {
        return [
            'edad' => (int) self::campo($evaluacion, 'edad'),
            'psoriasis_meses' => (int) self::campo($evaluacion, 'psoriasis_meses'),
            'pasi' => (float) self::campo($evaluacion, 'pasi'),
            'bsa' => (float) self::campo($evaluacion, 'bsa'),
            'historial_tb' => self::booleano(self::campo($evaluacion, 'historial_tb')),
            'embarazo' => self::booleano(self::campo($evaluacion, 'embarazo')),
            'uso_biologicos_meses' => (int) self::campo($evaluacion, 'uso_biologicos_meses'),
            'uso_corticoides_topicos_dias' => (int) self::campo($evaluacion, 'uso_corticoides_topicos_dias'),
        ];
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
