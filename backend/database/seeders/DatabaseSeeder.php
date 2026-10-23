<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Marca de tiempo base (milisegundos) de los UUID v7 deterministas:
     * 2026-01-01T00:00:00Z.
     */
    private const TIMESTAMP_BASE_MS = 1767225600000;

    /**
     * Carga los datos iniciales del motor: usuarios de la demostración, reglas
     * de criterio del protocolo y perfiles sintéticos de pacientes.
     */
    public function run(): void
    {
        $this->semillaUsuarios();

        $this->call(ReglaCriterioSeeder::class);
        $this->call(PerfilSinteticoSeeder::class);
    }

    /**
     * Genera un UUID v7 determinista a partir de una semilla textual.
     *
     * El proyecto prohíbe identificadores autoincrementales y exige UUID v4 o v7
     * (NFR012). La derivación es determinista para que la siembra pueda
     * repetirse sin duplicar registros: la misma semilla devuelve siempre el
     * mismo identificador.
     */
    public static function uuidV7(string $semilla): string
    {
        $timestamp = hex2bin(str_pad(dechex(self::TIMESTAMP_BASE_MS), 12, '0', STR_PAD_LEFT));
        $aleatorio = hash('sha256', $semilla, true);

        $bytes = $timestamp.substr($aleatorio, 0, 2).substr($aleatorio, 2, 8);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Siembra los usuarios de la demostración: el investigador clínico que
     * evalúa pacientes y el gestor del ensayo que audita las decisiones.
     */
    private function semillaUsuarios(): void
    {
        $usuarios = [
            [
                'id' => self::uuidV7('usuario:investigador.ensayo@example.com'),
                'email' => 'investigador.ensayo@example.com',
                'nombre' => 'Dra. Ana Ruiz',
                'rol' => 'investigador',
            ],
            [
                'id' => self::uuidV7('usuario:gestor.ensayo@example.com'),
                'email' => 'gestor.ensayo@example.com',
                'nombre' => 'Dr. Hernán Palacios',
                'rol' => 'gestor',
            ],
        ];

        DB::table('USUARIO')->upsert($usuarios, ['email'], ['nombre', 'rol']);
    }
}
