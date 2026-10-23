<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PerfilSinteticoSeeder extends Seeder
{
    /**
     * Usuario al que se atribuyen las evaluaciones sembradas.
     */
    private const EMAIL_INVESTIGADOR = 'investigador.ensayo@example.com';

    /**
     * Acción de auditoría registrada al crear cada evaluación.
     */
    private const ACCION_CREAR_EVALUACION = 'crear_evaluacion';

    /**
     * Momento inicial de la serie de evaluaciones sembradas.
     */
    private const FECHA_EVALUACION_BASE = '2026-10-23 08:00:00';

    /**
     * Umbrales del protocolo ALM-PSO-2026.
     */
    private const EDAD_MINIMA = 18;
    private const PSORIASIS_MINIMA_MESES = 6;
    private const PASI_MINIMO = 10;
    private const BSA_MINIMO = 10;
    private const BIOLOGICOS_MAXIMOS_MESES = 3;
    private const CORTICOIDES_MAXIMOS_DIAS = 14;

    /**
     * Orden de presentación de los criterios en la trazabilidad: primero los
     * de inclusión y después los de exclusión.
     */
    private const ORDEN_CRITERIOS = [
        ReglaCriterioSeeder::CODIGO_EDAD_MIN,
        ReglaCriterioSeeder::CODIGO_PSORIASIS_CRONICA,
        ReglaCriterioSeeder::CODIGO_PASI_MIN,
        ReglaCriterioSeeder::CODIGO_BSA_MIN,
        ReglaCriterioSeeder::CODIGO_TB_HISTORIAL,
        ReglaCriterioSeeder::CODIGO_EMBARAZO,
        ReglaCriterioSeeder::CODIGO_BIOLOGICOS_90D,
        ReglaCriterioSeeder::CODIGO_CORTICOIDES_14D,
    ];

    /**
     * Siembra los 15 perfiles sintéticos del protocolo con su evaluación, su
     * resultado, el desglose de criterios y su registro de auditoría.
     *
     * Todos los datos son ficticios: el proyecto no procesa PHI real (NFR008).
     */
    public function run(): void
    {
        $usuario = DB::table('USUARIO')->where('email', self::EMAIL_INVESTIGADOR)->first();

        if ($usuario === null) {
            throw new RuntimeException(
                'El usuario investigador no existe. Ejecute DatabaseSeeder para sembrar los datos iniciales.'
            );
        }

        $reglas = $this->reglasActivas();

        $pacientes = [];
        $evaluaciones = [];
        $resultados = [];
        $criterios = [];
        $auditorias = [];

        foreach ($this->perfiles() as $indice => $perfil) {
            $historia = $perfil['numero_historia_clinica'];
            $evaluadaEn = Carbon::parse(self::FECHA_EVALUACION_BASE)->addMinutes($indice);

            $pacienteId = DatabaseSeeder::uuidV7('paciente:'.$historia);
            $evaluacionId = DatabaseSeeder::uuidV7('evaluacion:'.$historia);

            $evaluacion = $this->evaluarPerfil($perfil['evaluacion'], $reglas);

            $pacientes[] = [
                'id' => $pacienteId,
                'numero_historia_clinica' => $historia,
                'fecha_nacimiento' => $perfil['fecha_nacimiento'],
                'genero' => $perfil['genero'],
                'ciudad' => $perfil['ciudad'],
            ];

            $evaluaciones[] = [
                'id' => $evaluacionId,
                'paciente_id' => $pacienteId,
                'usuario_id' => (string) $usuario->id,
                'edad' => (int) $perfil['evaluacion']['edad'],
                'psoriasis_meses' => (int) $perfil['evaluacion']['psoriasis_meses'],
                'pasi' => (float) $perfil['evaluacion']['pasi'],
                'bsa' => (float) $perfil['evaluacion']['bsa'],
                'historial_tb' => $perfil['evaluacion']['historial_tb'] ? 1 : 0,
                'embarazo' => $perfil['evaluacion']['embarazo'] ? 1 : 0,
                'uso_biologicos_meses' => (int) $perfil['evaluacion']['uso_biologicos_meses'],
                'uso_corticoides_topicos_dias' => (int) $perfil['evaluacion']['uso_corticoides_topicos_dias'],
                'evaluada_en' => $evaluadaEn->format('Y-m-d H:i:s'),
            ];

            $resultados[] = [
                'id' => DatabaseSeeder::uuidV7('resultado:'.$historia),
                'evaluacion_id' => $evaluacionId,
                'resultado' => $evaluacion['resultado'],
                'detalle_trazabilidad' => $evaluacion['detalle_trazabilidad'],
                'creada_en' => $evaluadaEn->copy()->addSecond()->format('Y-m-d H:i:s'),
            ];

            foreach ($evaluacion['criterios'] as $criterio) {
                $criterios[] = [
                    'id' => DatabaseSeeder::uuidV7('evaluacion_criterio:'.$historia.':'.$criterio['codigo']),
                    'evaluacion_id' => $evaluacionId,
                    'criterio_id' => $criterio['criterio_id'],
                    'estado' => $criterio['estado'],
                    'motivo' => $criterio['motivo'],
                ];
            }

            $auditorias[] = [
                'id' => DatabaseSeeder::uuidV7('auditoria:'.$historia),
                'usuario_id' => (string) $usuario->id,
                'evaluacion_id' => $evaluacionId,
                'accion' => self::ACCION_CREAR_EVALUACION,
                'timestamp' => $evaluadaEn->format('Y-m-d H:i:s'),
            ];

            $this->command?->line(sprintf(
                '  %s → %s (%s)',
                $historia,
                $evaluacion['resultado'],
                $perfil['escenario']
            ));
        }

        DB::table('PACIENTE')->upsert($pacientes, ['numero_historia_clinica'], ['fecha_nacimiento', 'genero', 'ciudad']);
        DB::table('EVALUACION')->upsert($evaluaciones, ['id'], [
            'paciente_id', 'usuario_id', 'edad', 'psoriasis_meses', 'pasi', 'bsa',
            'historial_tb', 'embarazo', 'uso_biologicos_meses', 'uso_corticoides_topicos_dias', 'evaluada_en',
        ]);
        DB::table('RESULTADO_EVALUACION')->upsert($resultados, ['evaluacion_id'], [
            'resultado', 'detalle_trazabilidad', 'creada_en',
        ]);
        DB::table('EVALUACION_CRITERIO')->upsert($criterios, ['id'], ['estado', 'motivo']);
        DB::table('AUDITORIA')->upsert($auditorias, ['id'], ['accion', 'timestamp']);
    }

    /**
     * Reglas de criterio activas ordenadas según el protocolo.
     *
     * @return array<int, object>
     */
    private function reglasActivas(): array
    {
        $reglas = DB::table('REGLA_CRITERIO')->where('activo', true)->get()->keyBy('codigo');

        $ordenadas = [];

        foreach (self::ORDEN_CRITERIOS as $codigo) {
            $regla = $reglas->get($codigo);

            if ($regla === null) {
                throw new RuntimeException(sprintf(
                    'La regla de criterio "%s" no está sembrada en REGLA_CRITERIO.',
                    $codigo
                ));
            }

            $ordenadas[] = $regla;
        }

        return $ordenadas;
    }

    /**
     * Evalúa un perfil contra el catálogo de criterios y resuelve el resultado.
     *
     * La lógica reproduce las reglas del protocolo para materializar el
     * conjunto sintético. En ejecución, la implementación autoritativa es el
     * motor de elegibilidad de la capa de Dominio.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, object>  $reglas
     * @return array<string, mixed>
     */
    private function evaluarPerfil(array $datos, array $reglas): array
    {
        $criterios = [];
        $inclusion = 0;
        $exclusion = 0;
        $fallosInclusion = [];
        $bloqueosExclusion = [];

        foreach ($reglas as $regla) {
            $codigo = (string) $regla->codigo;
            $nombre = (string) $regla->nombre;
            $veredicto = $this->evaluarCriterio($codigo, $datos);

            $criterios[] = [
                'criterio_id' => (string) $regla->id,
                'codigo' => $codigo,
                'nombre' => $nombre,
                'estado' => $veredicto['estado'],
                'motivo' => $veredicto['motivo'],
            ];

            if ((string) $regla->tipo === ReglaCriterioSeeder::TIPO_INCLUSION) {
                $inclusion++;

                if ($veredicto['estado'] === 'fail') {
                    $fallosInclusion[] = $nombre;
                }

                continue;
            }

            $exclusion++;

            if ($veredicto['estado'] === 'fail') {
                $bloqueosExclusion[] = $nombre;
            }
        }

        $resultado = ($fallosInclusion === [] && $bloqueosExclusion === []) ? 'elegible' : 'no_elegible';

        return [
            'resultado' => $resultado,
            'criterios' => $criterios,
            'detalle_trazabilidad' => $this->detalleTrazabilidad(
                $inclusion,
                $fallosInclusion,
                $exclusion,
                $bloqueosExclusion
            ),
        ];
    }

    /**
     * Evalúa un criterio contra los datos clínicos de la evaluación.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado: string, motivo: string|null}
     */
    private function evaluarCriterio(string $codigo, array $datos): array
    {
        return match ($codigo) {
            ReglaCriterioSeeder::CODIGO_EDAD_MIN => $this->cumple(
                (int) $datos['edad'] >= self::EDAD_MINIMA,
                sprintf(
                    'Edad %d: se requiere una edad mayor o igual a %d.',
                    (int) $datos['edad'],
                    self::EDAD_MINIMA
                )
            ),
            ReglaCriterioSeeder::CODIGO_PSORIASIS_CRONICA => $this->cumple(
                (int) $datos['psoriasis_meses'] >= self::PSORIASIS_MINIMA_MESES,
                sprintf(
                    'Psoriasis diagnosticada hace %d meses: se requieren al menos %d meses de evolución.',
                    (int) $datos['psoriasis_meses'],
                    self::PSORIASIS_MINIMA_MESES
                )
            ),
            ReglaCriterioSeeder::CODIGO_PASI_MIN => $this->cumple(
                (float) $datos['pasi'] >= self::PASI_MINIMO,
                sprintf(
                    'PASI %.1f: se requiere un valor mayor o igual a %d.',
                    (float) $datos['pasi'],
                    self::PASI_MINIMO
                )
            ),
            ReglaCriterioSeeder::CODIGO_BSA_MIN => $this->cumple(
                (float) $datos['bsa'] >= self::BSA_MINIMO,
                sprintf(
                    'BSA %.1f%%: se requiere un valor mayor o igual a %d%%.',
                    (float) $datos['bsa'],
                    self::BSA_MINIMO
                )
            ),
            ReglaCriterioSeeder::CODIGO_TB_HISTORIAL => $this->cumple(
                ! (bool) $datos['historial_tb'],
                'El paciente presenta historial de tuberculosis.'
            ),
            ReglaCriterioSeeder::CODIGO_EMBARAZO => $this->cumple(
                ! (bool) $datos['embarazo'],
                'El paciente está embarazada.'
            ),
            ReglaCriterioSeeder::CODIGO_BIOLOGICOS_90D => $this->cumple(
                (int) $datos['uso_biologicos_meses'] < self::BIOLOGICOS_MAXIMOS_MESES,
                sprintf(
                    'Uso de biológicos en los últimos %d meses: no se admiten fármacos biológicos en los últimos 90 días.',
                    (int) $datos['uso_biologicos_meses']
                )
            ),
            ReglaCriterioSeeder::CODIGO_CORTICOIDES_14D => $this->cumple(
                (int) $datos['uso_corticoides_topicos_dias'] < self::CORTICOIDES_MAXIMOS_DIAS,
                sprintf(
                    'Uso de corticoides tópicos en los últimos %d días: no se admiten corticoides en los últimos 14 días.',
                    (int) $datos['uso_corticoides_topicos_dias']
                )
            ),
            default => throw new RuntimeException(sprintf(
                'La regla de criterio "%s" no tiene una regla de evaluación definida.',
                $codigo
            )),
        };
    }

    /**
     * Traduce el cumplimiento de un criterio al estado de EVALUACION_CRITERIO.
     *
     * @return array{estado: string, motivo: string|null}
     */
    private function cumple(bool $cumple, string $motivoFallo): array
    {
        return [
            'estado' => $cumple ? 'pass' : 'fail',
            'motivo' => $cumple ? null : $motivoFallo,
        ];
    }

    /**
     * Construye el detalle de trazabilidad que acompaña a la decisión.
     *
     * @param  array<int, string>  $fallosInclusion
     * @param  array<int, string>  $bloqueosExclusion
     */
    private function detalleTrazabilidad(
        int $inclusion,
        array $fallosInclusion,
        int $exclusion,
        array $bloqueosExclusion
    ): string {
        if ($fallosInclusion === []) {
            $lineaInclusion = sprintf('Inclusión: %d de %d criterios cumplidos.', $inclusion, $inclusion);
        } else {
            $lineaInclusion = sprintf(
                'Inclusión: %d de %d criterios cumplidos. No se cumplieron: %s.',
                $inclusion - count($fallosInclusion),
                $inclusion,
                implode(', ', $fallosInclusion)
            );
        }

        if ($bloqueosExclusion === []) {
            $lineaExclusion = 'Exclusión: ningún criterio de exclusión bloqueó la elegibilidad.';
        } else {
            $lineaExclusion = sprintf(
                'Exclusión: %d de %d criterios de exclusión bloqueó la elegibilidad: %s.',
                count($bloqueosExclusion),
                $exclusion,
                implode(', ', $bloqueosExclusion)
            );
        }

        return $lineaInclusion."\n".$lineaExclusion;
    }

    /**
     * Perfiles sintéticos del protocolo ALM-PSO-2026.
     *
     * El conjunto cubre el Golden Path, los casos límite de los umbrales de
     * inclusión, el falso positivo aparente y los descartes por un único
     * criterio o por criterios múltiples.
     *
     * @return array<int, array<string, mixed>>
     */
    private function perfiles(): array
    {
        return [
            [
                'numero_historia_clinica' => 'HC-PSO-0001',
                'escenario' => 'Golden Path: cumple todos los criterios de inclusión y ninguno de exclusión',
                'fecha_nacimiento' => '1984-03-11',
                'genero' => 'femenino',
                'ciudad' => 'Bogotá',
                'evaluacion' => [
                    'edad' => 42,
                    'psoriasis_meses' => 36,
                    'pasi' => 18.4,
                    'bsa' => 22.5,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 3,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0002',
                'escenario' => 'Golden Path: severidad alta sin exposición a fármacos de exclusión',
                'fecha_nacimiento' => '1991-07-24',
                'genero' => 'masculino',
                'ciudad' => 'Medellín',
                'evaluacion' => [
                    'edad' => 35,
                    'psoriasis_meses' => 24,
                    'pasi' => 14.2,
                    'bsa' => 16.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0003',
                'escenario' => 'Caso límite: valores mínimos de todos los criterios de inclusión',
                'fecha_nacimiento' => '2008-02-28',
                'genero' => 'femenino',
                'ciudad' => 'Cali',
                'evaluacion' => [
                    'edad' => 18,
                    'psoriasis_meses' => 6,
                    'pasi' => 10.0,
                    'bsa' => 10.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0004',
                'escenario' => 'Caso límite: uso por debajo de los umbrales de exclusión',
                'fecha_nacimiento' => '1997-05-17',
                'genero' => 'masculino',
                'ciudad' => 'Barranquilla',
                'evaluacion' => [
                    'edad' => 29,
                    'psoriasis_meses' => 11,
                    'pasi' => 12.5,
                    'bsa' => 13.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 2,
                    'uso_corticoides_topicos_dias' => 13,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0005',
                'escenario' => 'Descarte por edad menor a 18 años',
                'fecha_nacimiento' => '2009-09-03',
                'genero' => 'femenino',
                'ciudad' => 'Pereira',
                'evaluacion' => [
                    'edad' => 17,
                    'psoriasis_meses' => 30,
                    'pasi' => 15.0,
                    'bsa' => 18.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0006',
                'escenario' => 'Descarte por psoriasis con menos de 6 meses de diagnóstico',
                'fecha_nacimiento' => '1974-11-08',
                'genero' => 'masculino',
                'ciudad' => 'Bucaramanga',
                'evaluacion' => [
                    'edad' => 51,
                    'psoriasis_meses' => 5,
                    'pasi' => 16.0,
                    'bsa' => 20.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0007',
                'escenario' => 'Descarte por PASI menor a 10',
                'fecha_nacimiento' => '1981-04-21',
                'genero' => 'femenino',
                'ciudad' => 'Manizales',
                'evaluacion' => [
                    'edad' => 45,
                    'psoriasis_meses' => 60,
                    'pasi' => 7.8,
                    'bsa' => 14.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0008',
                'escenario' => 'Descarte por BSA menor a 10%',
                'fecha_nacimiento' => '1988-08-15',
                'genero' => 'masculino',
                'ciudad' => 'Armenia',
                'evaluacion' => [
                    'edad' => 38,
                    'psoriasis_meses' => 84,
                    'pasi' => 12.0,
                    'bsa' => 6.5,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0009',
                'escenario' => 'Descarte por historial de tuberculosis',
                'fecha_nacimiento' => '1965-01-30',
                'genero' => 'masculino',
                'ciudad' => 'Cartagena',
                'evaluacion' => [
                    'edad' => 61,
                    'psoriasis_meses' => 120,
                    'pasi' => 21.0,
                    'bsa' => 25.0,
                    'historial_tb' => true,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0010',
                'escenario' => 'Descarte por embarazo',
                'fecha_nacimiento' => '1998-06-05',
                'genero' => 'femenino',
                'ciudad' => 'Cúcuta',
                'evaluacion' => [
                    'edad' => 28,
                    'psoriasis_meses' => 48,
                    'pasi' => 19.5,
                    'bsa' => 24.0,
                    'historial_tb' => false,
                    'embarazo' => true,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0011',
                'escenario' => 'Descarte por uso de biológicos en los últimos 90 días',
                'fecha_nacimiento' => '1993-10-19',
                'genero' => 'masculino',
                'ciudad' => 'Santa Marta',
                'evaluacion' => [
                    'edad' => 33,
                    'psoriasis_meses' => 72,
                    'pasi' => 17.0,
                    'bsa' => 19.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 3,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0012',
                'escenario' => 'Descarte por uso de corticoides tópicos en los últimos 14 días',
                'fecha_nacimiento' => '1977-12-02',
                'genero' => 'femenino',
                'ciudad' => 'Ibagué',
                'evaluacion' => [
                    'edad' => 48,
                    'psoriasis_meses' => 96,
                    'pasi' => 24.0,
                    'bsa' => 30.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 14,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0013',
                'escenario' => 'Descarte por dos criterios de exclusión simultáneos',
                'fecha_nacimiento' => '2000-03-27',
                'genero' => 'femenino',
                'ciudad' => 'Villavicencio',
                'evaluacion' => [
                    'edad' => 26,
                    'psoriasis_meses' => 20,
                    'pasi' => 13.0,
                    'bsa' => 15.5,
                    'historial_tb' => true,
                    'embarazo' => true,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0014',
                'escenario' => 'Falso positivo aparente: severidad suficiente con diagnóstico reciente',
                'fecha_nacimiento' => '1995-09-13',
                'genero' => 'masculino',
                'ciudad' => 'Pasto',
                'evaluacion' => [
                    'edad' => 30,
                    'psoriasis_meses' => 5,
                    'pasi' => 11.0,
                    'bsa' => 12.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 0,
                ],
            ],
            [
                'numero_historia_clinica' => 'HC-PSO-0015',
                'escenario' => 'Golden Path: psoriasis crónica de severidad máxima',
                'fecha_nacimiento' => '1965-05-09',
                'genero' => 'femenino',
                'ciudad' => 'Neiva',
                'evaluacion' => [
                    'edad' => 60,
                    'psoriasis_meses' => 180,
                    'pasi' => 48.5,
                    'bsa' => 62.0,
                    'historial_tb' => false,
                    'embarazo' => false,
                    'uso_biologicos_meses' => 0,
                    'uso_corticoides_topicos_dias' => 7,
                ],
            ],
        ];
    }
}
