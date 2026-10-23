/**
 * Contratos de datos compartidos entre el backend Laravel y el frontend React.
 *
 * Cada interfaz replica uno a uno los DTOs declarados en
 * backend/app/Application/DTOs, de modo que la respuesta de la API pueda
 * consumirse sin conversiones ni campos intermedios (NFR015).
 */

/**
 * Ficha demográfica del paciente evaluado.
 */
export interface PacienteDTO {
  id: string;
  numero_historia_clinica: string;
  /** Fecha de nacimiento en formato ISO (AAAA-MM-DD). */
  fecha_nacimiento: string;
  genero: string;
  ciudad: string;
}

/**
 * Parámetros clínicos registrados en una evaluación de elegibilidad.
 */
export interface EvaluacionDTO {
  id: string;
  paciente_id: string;
  usuario_id: string;
  edad: number;
  psoriasis_meses: number;
  pasi: number;
  bsa: number;
  historial_tb: boolean;
  embarazo: boolean;
  uso_biologicos_meses: number;
  uso_corticoides_topicos_dias: number;
  /** Marca de tiempo ISO 8601 de la evaluación. */
  evaluada_en: string;
}

/**
 * Resultado calculado por el motor de elegibilidad.
 */
export interface ResultadoEvaluacionDTO {
  id: string;
  evaluacion_id: string;
  resultado: 'elegible' | 'no_elegible' | 'revision';
  detalle_trazabilidad: string;
  /** Marca de tiempo ISO 8601 de creación del resultado. */
  creada_en: string;
}

/**
 * Regla del catálogo de criterios del protocolo ALM-PSO-2026.
 */
export interface ReglaCriterioDTO {
  id: string;
  codigo: string;
  nombre: string;
  tipo: 'inclusion' | 'exclusion';
  descripcion: string;
  activo: boolean;
}

/**
 * Estado de un criterio tras aplicar su regla.
 */
export interface EvaluacionCriterioDTO {
  id: string;
  evaluacion_id: string;
  criterio_id: string;
  estado: 'pass' | 'fail' | 'na';
  motivo?: string | null;
}

/**
 * Usuario del protocolo con acceso al motor de pre-screening.
 */
export interface UsuarioDTO {
  id: string;
  email: string;
  nombre: string;
  rol: 'investigador' | 'gestor';
}

/**
 * Registro de auditoría de una decisión del motor.
 */
export interface AuditoriaDTO {
  id: string;
  usuario_id: string;
  evaluacion_id: string;
  accion: 'crear_evaluacion' | 'consultar_resolucion' | 'exportar_auditoria' | 'consultar_estadisticas';
  timestamp: string;
}

/**
 * Cuerpo de la solicitud de creación de una evaluación clínica.
 */
export interface CrearEvaluacionRequest {
  paciente: {
    numero_historia_clinica: string;
    fecha_nacimiento: string;
    genero: string;
    ciudad: string;
  };
  evaluacion: {
    edad: number;
    psoriasis_meses: number;
    pasi: number;
    bsa: number;
    historial_tb: boolean;
    embarazo: boolean;
    uso_biologicos_meses: number;
    uso_corticoides_topicos_dias: number;
  };
}

/**
 * Criterio evaluado con el detalle de la regla aplicada.
 */
export interface EvaluacionCriterioDTODetalle extends EvaluacionCriterioDTO {
  codigo: string;
  nombre: string;
  tipo: 'inclusion' | 'exclusion';
}

/**
 * Respuesta completa de una evaluación de elegibilidad.
 */
export interface EvaluacionResolutionResponse {
  evaluacion: EvaluacionDTO;
  paciente: PacienteDTO;
  resultado: ResultadoEvaluacionDTO;
  criterios: EvaluacionCriterioDTODetalle[];
}

/**
 * Métricas de reclutamiento del protocolo ALM-PSO-2026.
 */
export interface EstadisticasReclutamientoDTO {
  total_evaluaciones: number;
  elegibles: number;
  no_elegibles: number;
  revision: number;
  tasa_elegibilidad: number;
  distribucion_motivos_exclusion: Array<{ motivo: string; cantidad: number }>;
  serie_temporal: Array<{ fecha: string; evaluaciones: number; elegibles: number }>;
}

/**
 * Filtros del panel de auditoría y estadísticas.
 */
export interface FiltrosAuditoria {
  fecha_desde?: string;
  fecha_hasta?: string;
  estado?: 'elegible' | 'no_elegible' | 'revision' | 'pendiente';
  usuario_id?: string;
  pagina?: number;
}
