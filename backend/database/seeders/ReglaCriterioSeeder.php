<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReglaCriterioSeeder extends Seeder
{
    /**
     * Códigos de las reglas de criterio del protocolo ALM-PSO-2026.
     */
    public const CODIGO_EDAD_MIN = 'CRIT_EDAD_MIN';
    public const CODIGO_PSORIASIS_CRONICA = 'CRIT_PSORIASIS_CRONICA';
    public const CODIGO_PASI_MIN = 'CRIT_PASI_MIN';
    public const CODIGO_BSA_MIN = 'CRIT_BSA_MIN';
    public const CODIGO_TB_HISTORIAL = 'CRIT_TB_HISTORIAL';
    public const CODIGO_EMBARAZO = 'CRIT_EMBARAZO';
    public const CODIGO_BIOLOGICOS_90D = 'CRIT_BIOLOGICOS_90D';
    public const CODIGO_CORTICOIDES_14D = 'CRIT_CORTICOIDES_14D';

    /**
     * Tipos de criterio admitidos por el catálogo.
     */
    public const TIPO_INCLUSION = 'inclusion';
    public const TIPO_EXCLUSION = 'exclusion';

    /**
     * Siembra el catálogo de reglas de criterio: cuatro criterios de inclusión
     * y cuatro de exclusión, con los umbrales del protocolo de psoriasis en
     * placas.
     */
    public function run(): void
    {
        DB::table('REGLA_CRITERIO')->upsert(
            $this->reglas(),
            ['codigo'],
            ['nombre', 'tipo', 'descripcion', 'activo']
        );
    }

    /**
     * Reglas de criterio con su código estable para el motor y la trazabilidad.
     *
     * @return array<int, array<string, mixed>>
     */
    private function reglas(): array
    {
        return [
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_EDAD_MIN),
                'codigo' => self::CODIGO_EDAD_MIN,
                'nombre' => 'Edad >= 18 años',
                'tipo' => self::TIPO_INCLUSION,
                'descripcion' => 'El paciente debe tener una edad mayor o igual a 18 años.',
                'activo' => true,
            ],
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_PSORIASIS_CRONICA),
                'codigo' => self::CODIGO_PSORIASIS_CRONICA,
                'nombre' => 'Diagnóstico crónico >= 6 meses',
                'tipo' => self::TIPO_INCLUSION,
                'descripcion' => 'El diagnóstico de psoriasis en placas debe ser crónico, con una evolución de al menos 6 meses.',
                'activo' => true,
            ],
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_PASI_MIN),
                'codigo' => self::CODIGO_PASI_MIN,
                'nombre' => 'PASI >= 10',
                'tipo' => self::TIPO_INCLUSION,
                'descripcion' => 'El índice de severidad PASI debe ser mayor o igual a 10.',
                'activo' => true,
            ],
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_BSA_MIN),
                'codigo' => self::CODIGO_BSA_MIN,
                'nombre' => 'BSA >= 10%',
                'tipo' => self::TIPO_INCLUSION,
                'descripcion' => 'El porcentaje de superficie corporal afectada (BSA) debe ser mayor o igual a 10%.',
                'activo' => true,
            ],
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_TB_HISTORIAL),
                'codigo' => self::CODIGO_TB_HISTORIAL,
                'nombre' => 'Historial de TB',
                'tipo' => self::TIPO_EXCLUSION,
                'descripcion' => 'El paciente no debe presentar historial de tuberculosis.',
                'activo' => true,
            ],
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_EMBARAZO),
                'codigo' => self::CODIGO_EMBARAZO,
                'nombre' => 'Embarazo',
                'tipo' => self::TIPO_EXCLUSION,
                'descripcion' => 'El paciente no debe estar embarazada.',
                'activo' => true,
            ],
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_BIOLOGICOS_90D),
                'codigo' => self::CODIGO_BIOLOGICOS_90D,
                'nombre' => 'Biológicos en los últimos 90 días',
                'tipo' => self::TIPO_EXCLUSION,
                'descripcion' => 'El paciente no debe haber usado fármacos biológicos en los últimos 90 días (3 meses).',
                'activo' => true,
            ],
            [
                'id' => DatabaseSeeder::uuidV7('regla_criterio:'.self::CODIGO_CORTICOIDES_14D),
                'codigo' => self::CODIGO_CORTICOIDES_14D,
                'nombre' => 'Corticoides en los últimos 14 días',
                'tipo' => self::TIPO_EXCLUSION,
                'descripcion' => 'El paciente no debe haber usado corticoides tópicos en los últimos 14 días.',
                'activo' => true,
            ],
        ];
    }
}
