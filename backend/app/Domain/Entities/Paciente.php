<?php

namespace App\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Entidad de dominio del paciente evaluado en el protocolo ALM-PSO-2026.
 *
 * Mapea la tabla PACIENTE. La clave primaria es un UUID v7 generado en el
 * dominio porque el proyecto prohíbe identificadores autoincrementales y
 * exige UUID v4 o v7 (NFR012).
 *
 * La entidad es pura: no depende del framework ni de la infraestructura.
 */
final class Paciente
{
    /**
     * Formato de la fecha de nacimiento persistido en PACIENTE.
     */
    public const FORMATO_FECHA_NACIMIENTO = 'Y-m-d';

    /**
     * Longitudes máximas de los campos de texto según el esquema.
     */
    private const LONGITUD_NUMERO_HISTORIA = 64;
    private const LONGITUD_GENERO = 32;
    private const LONGITUD_CIUDAD = 128;

    public readonly string $id;

    public readonly string $numero_historia_clinica;

    public readonly string $fecha_nacimiento;

    public readonly string $genero;

    public readonly string $ciudad;

    /**
     * Construye el paciente validando los invariantes de dominio.
     */
    public function __construct(
        string $id,
        string $numero_historia_clinica,
        string $fecha_nacimiento,
        string $genero,
        string $ciudad
    ) {
        $this->id = self::validarUuid($id, 'id');
        $this->numero_historia_clinica = self::validarTexto(
            $numero_historia_clinica,
            'numero_historia_clinica',
            self::LONGITUD_NUMERO_HISTORIA
        );
        $this->fecha_nacimiento = self::validarFecha(
            $fecha_nacimiento,
            'fecha_nacimiento',
            self::FORMATO_FECHA_NACIMIENTO
        );
        $this->genero = self::validarTexto($genero, 'genero', self::LONGITUD_GENERO);
        $this->ciudad = self::validarTexto($ciudad, 'ciudad', self::LONGITUD_CIUDAD);
    }

    /**
     * Crea un paciente con un identificador UUID v7 generado por el dominio.
     */
    public static function crear(
        string $numero_historia_clinica,
        string $fecha_nacimiento,
        string $genero,
        string $ciudad,
        ?string $id = null
    ): self {
        return new self(
            id: $id ?? self::nuevoId(),
            numero_historia_clinica: $numero_historia_clinica,
            fecha_nacimiento: $fecha_nacimiento,
            genero: $genero,
            ciudad: $ciudad
        );
    }

    /**
     * Serializa la entidad con los nombres de columna de PACIENTE.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_historia_clinica' => $this->numero_historia_clinica,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
            'ciudad' => $this->ciudad,
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

    /**
     * Valida que una fecha tenga el formato esperado y sea una fecha real.
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
