# SPEC.md

Project name: **Motor de Pre-selección Clínica Etapa PO**  
Domain description: **Motor Clínico Evaluación Pacientes Psoriasis**  
Architecture reference: **ALM-PSO-2026 · Motor de Pre-screening Clínico para Psoriasis en Placas**

---

## 1. TECHNOLOGY STACK

| Layer | Technology | Version / Notes |
|---|---|---|
| Backend language | PHP | 8.3 |
| Backend framework | Laravel | 11 |
| API authentication | Laravel Passport | ^12 (configured with Cognito JWT Bearer validation via `auth:api`) |
| Database | Amazon RDS for MySQL | MySQL 8.0 |
| Cache / session | Amazon ElastiCache for Redis | Redis 7.x engine |
| Frontend | React | 18 |
| Frontend language | TypeScript | 5.x |
| Frontend build | Vite | 5.x |
| Frontend auth | aws-amplify | @aws-amplify/auth ^6 |
| Container runtime | Docker | latest stable |
| Orchestration | Amazon ECS / AWS Fargate | ECS on Fargate, rolling update |
| Frontend hosting | Amazon S3 | static website hosting |
| CDN | Amazon CloudFront | distribution with S3 origin |
| Identity provider | Amazon Cognito | user pool `ALM-PSO-2026` |
| Secrets | AWS Secrets Manager | DB and service credentials |
| Configuration | AWS AppConfig | dynamic application parameters |
| Observability | AWS CloudWatch | structured logs and ECS metrics |
| Testing framework | Pest PHP | listed in architecture context; **no test files are generated in this scope** because tests are outside the user-approved scope. The 80% coverage/`arch()` requirement is documented in §10 as a non-implemented constraint. |

---

## 2. DATA CONTRACTS

The Architect Database Schema Contract is authoritative. Entity names, field names, and relationships must match exactly. All primary keys use UUID v4/v7 strings. No autoincrement IDs may be used.

### 2.1 Backend persistence contracts (MySQL tables)

#### PACIENTE
| Field | Type | Constraints |
|---|---|---|
| id | char(36) | PK, UUID v7 |
| numero_historia_clinica | varchar(64) | not null, unique |
| fecha_nacimiento | date | not null |
| genero | varchar(32) | not null |
| ciudad | varchar(128) | not null |

#### EVALUACION
| Field | Type | Constraints |
|---|---|---|
| id | char(36) | PK, UUID v7 |
| paciente_id | char(36) | FK → PACIENTE.id, not null |
| usuario_id | char(36) | FK → USUARIO.id, not null (implementation FK required by `USUARIO ||--o{ EVALUACION : crea`) |
| edad | int | not null |
| psoriasis_meses | int | not null |
| pasi | float | not null |
| bsa | float | not null |
| historial_tb | tinyint(1) | not null, boolean |
| embarazo | tinyint(1) | not null, boolean |
| uso_biologicos_meses | int | not null |
| uso_corticoides_topicos_dias | int | not null |
| evaluada_en | datetime | not null |

#### RESULTADO_EVALUACION
| Field | Type | Constraints |
|---|---|---|
| id | char(36) | PK, UUID v7 |
| evaluacion_id | char(36) | FK → EVALUACION.id, not null, unique |
| resultado | varchar(32) | not null, allowed: `elegible`, `no_elegible`, `revision` |
| detalle_trazabilidad | text | not null |
| creada_en | datetime | not null |

#### REGLA_CRITERIO
| Field | Type | Constraints |
|---|---|---|
| id | char(36) | PK, UUID v7 |
| codigo | varchar(64) | not null, unique |
| nombre | varchar(128) | not null |
| tipo | varchar(32) | not null, allowed: `inclusion`, `exclusion` |
| descripcion | varchar(255) | not null |
| activo | tinyint(1) | not null, boolean default true |

#### EVALUACION_CRITERIO
| Field | Type | Constraints |
|---|---|---|
| id | char(36) | PK, UUID v7 |
| evaluacion_id | char(36) | FK → EVALUACION.id, not null |
| criterio_id | char(36) | FK → REGLA_CRITERIO.id, not null |
| estado | varchar(32) | not null, allowed: `pass`, `fail`, `na` |
| motivo | varchar(255) | nullable |

#### USUARIO
| Field | Type | Constraints |
|---|---|---|
| id | char(36) | PK, UUID v7 |
| email | varchar(255) | not null, unique |
| nombre | varchar(128) | not null |
| rol | varchar(32) | not null, allowed: `investigador`, `gestor` |

#### AUDITORIA
| Field | Type | Constraints |
|---|---|---|
| id | char(36) | PK, UUID v7 |
| usuario_id | char(36) | FK → USUARIO.id, not null |
| evaluacion_id | char(36) | FK → EVALUACION.id, not null |
| accion | varchar(64) | not null, allowed: `crear_evaluacion`, `consultar_resolucion`, `exportar_auditoria`, `consultar_estadisticas` |
| timestamp | datetime | not null |

### 2.2 Backend DTOs (PHP value objects)

```php
final class PacienteDTO {
    public string $id;
    public string $numero_historia_clinica;
    public string $fecha_nacimiento;
    public string $genero;
    public string $ciudad;
}

final class EvaluacionDTO {
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
}

final class ResultadoEvaluacionDTO {
    public string $id;
    public string $evaluacion_id;
    public string $resultado;
    public string $detalle_trazabilidad;
    public string $creada_en;
}

final class ReglaCriterioDTO {
    public string $id;
    public string $codigo;
    public string $nombre;
    public string $tipo;
    public string $descripcion;
    public bool $activo;
}

final class EvaluacionCriterioDTO {
    public string $id;
    public string $evaluacion_id;
    public string $criterio_id;
    public string $estado;
    public ?string $motivo;
}

final class UsuarioDTO {
    public string $id;
    public string $email;
    public string $nombre;
    public string $rol;
}

final class AuditoriaDTO {
    public string $id;
    public string $usuario_id;
    public string $evaluacion_id;
    public string $accion;
    public string $timestamp;
}
```

### 2.3 Frontend TypeScript contracts

The TypeScript interfaces must mirror the backend DTOs field-for-field.

```typescript
export interface PacienteDTO {
  id: string;
  numero_historia_clinica: string;
  fecha_nacimiento: string; // YYYY-MM-DD
  genero: string;
  ciudad: string;
}

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
  evaluada_en: string;
}

export interface ResultadoEvaluacionDTO {
  id: string;
  evaluacion_id: string;
  resultado: 'elegible' | 'no_elegible' | 'revision';
  detalle_trazabilidad: string;
  creada_en: string;
}

export interface ReglaCriterioDTO {
  id: string;
  codigo: string;
  nombre: string;
  tipo: 'inclusion' | 'exclusion';
  descripcion: string;
  activo: boolean;
}

export interface EvaluacionCriterioDTO {
  id: string;
  evaluacion_id: string;
  criterio_id: string;
  estado: 'pass' | 'fail' | 'na';
  motivo?: string | null;
}

export interface UsuarioDTO {
  id: string;
  email: string;
  nombre: string;
  rol: 'investigador' | 'gestor';
}

export interface AuditoriaDTO {
  id: string;
  usuario_id: string;
  evaluacion_id: string;
  accion: 'crear_evaluacion' | 'consultar_resolucion' | 'exportar_auditoria' | 'consultar_estadisticas';
  timestamp: string;
}

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

export interface EvaluacionResolutionResponse {
  evaluacion: EvaluacionDTO;
  paciente: PacienteDTO;
  resultado: ResultadoEvaluacionDTO;
  criterios: EvaluacionCriterioDTODetalle[];
}

export interface EvaluacionCriterioDTODetalle extends EvaluacionCriterioDTO {
  codigo: string;
  nombre: string;
  tipo: 'inclusion' | 'exclusion';
}

export interface EstadisticasReclutamientoDTO {
  total_evaluaciones: number;
  elegibles: number;
  no_elegibles: number;
  revision: number;
  tasa_elegibilidad: number;
  distribucion_motivos_exclusion: Array<{ motivo: string; cantidad: number }>;
  serie_temporal: Array<{ fecha: string; evaluaciones: number; elegibles: number }>;
}

export interface FiltrosAuditoria {
  fecha_desde?: string;
  fecha_hasta?: string;
  estado?: 'elegible' | 'no_elegible' | 'revision' | 'pendiente';
  usuario_id?: string;
  pagina?: number;
}
```

---

## 3. API ENDPOINTS

All routes are prefixed with `/api/v1` and protected by `auth:api` except `/api/v1/health`. The JWT Bearer token is obtained through Amazon Cognito / aws-amplify on the frontend.

### 3.1 Health

| Method | Path | Request | Response |
|---|---|---|---|
| GET | `/api/v1/health` | — | `200` `{ "status": "ok", "service": "motor-clinico-psoriasis", "timestamp": "ISO8601" }` |

Used by ECS health checks. No authentication required.

### 3.2 Evaluations

#### POST `/api/v1/evaluaciones`
Creates or updates a patient, creates an EVALUACION, runs the clinical eligibility engine, persists RESULTADO_EVALUACION and EVALUACION_CRITERIO rows, and writes an AUDITORIA row with `accion=crear_evaluacion`.

Request body (JSON):
```json
{
  "paciente": {
    "numero_historia_clinica": "HC-000123",
    "fecha_nacimiento": "1980-01-01",
    "genero": "femenino",
    "ciudad": "Bogotá"
  },
  "evaluacion": {
    "edad": 44,
    "psoriasis_meses": 24,
    "pasi": 12.5,
    "bsa": 15.0,
    "historial_tb": false,
    "embarazo": false,
    "uso_biologicos_meses": 0,
    "uso_corticoides_topicos_dias": 5
  }
}
```

Response `201`:
```json
{
  "evaluacion_id": "uuid",
  "resultado": "elegible",
  "detalle_trazabilidad": "Inclusión: ... Exclusión: ...",
  "criterios": [
    {
      "codigo": "CRIT_EDAD_MIN",
      "nombre": "Edad mínima 18 años",
      "tipo": "inclusion",
      "estado": "pass",
      "motivo": null
    }
  ]
}
```

#### GET `/api/v1/evaluaciones`
Lists recent evaluations with optional filters.

Query parameters:
- `fecha_desde` (optional date)
- `fecha_hasta` (optional date)
- `estado` (optional: `elegible`, `no_elegible`, `revision`)
- `paciente_q` (optional search by `numero_historia_clinica`)
- `page` (optional, default 1)

Response `200`:
```json
{
  "data": [
    {
      "evaluacion_id": "uuid",
      "paciente_id": "uuid",
      "numero_historia_clinica": "HC-000123",
      "edad": 44,
      "pasi": 12.5,
      "bsa": 15.0,
      "resultado": "elegible",
      "evaluada_en": "2026-10-23T08:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 15
  }
}
```

#### GET `/api/v1/evaluaciones/{evaluacionId}`
Returns full resolution detail.

Response `200`:
```json
{
  "evaluacion": {
    "id": "uuid",
    "paciente_id": "uuid",
    "usuario_id": "uuid",
    "edad": 44,
    "psoriasis_meses": 24,
    "pasi": 12.5,
    "bsa": 15.0,
    "historial_tb": false,
    "embarazo": false,
    "uso_biologicos_meses": 0,
    "uso_corticoides_topicos_dias": 5,
    "evaluada_en": "2026-10-23T08:00:00Z"
  },
  "paciente": {
    "id": "uuid",
    "numero_historia_clinica": "HC-000123",
    "fecha_nacimiento": "1980-01-01",
    "genero": "femenino",
    "ciudad": "Bogotá"
  },
  "resultado": {
    "id": "uuid",
    "evaluacion_id": "uuid",
    "resultado": "elegible",
    "detalle_trazabilidad": "...",
    "creada_en": "2026-10-23T08:00:01Z"
  },
  "criterios": [
    {
      "id": "uuid",
      "codigo": "CRIT_EDAD_MIN",
      "nombre": "Edad mínima 18 años",
      "tipo": "inclusion",
      "estado": "pass",
      "motivo": null
    }
  ]
}
```

#### GET `/api/v1/evaluaciones/{evaluacionId}/export`
Returns the evaluation resolution as a JSON file download (`application/json`). This satisfies the export/traceability action on the resolution panel.

### 3.3 Criteria

#### GET `/api/v1/criterios`
Returns all active criteria rules.

Query parameters:
- `activo` (optional boolean, default `true`)
- `tipo` (optional: `inclusion`, `exclusion`)

Response `200`:
```json
{
  "data": [
    {
      "id": "uuid",
      "codigo": "CRIT_EDAD_MIN",
      "nombre": "Edad mínima 18 años",
      "tipo": "inclusion",
      "descripcion": "El paciente debe tener 18 años o más.",
      "activo": true
    }
  ]
}
```

### 3.4 Audit and statistics

#### GET `/api/v1/auditoria`
Lists AUDITORIA rows with query filters.

Query parameters:
- `fecha_desde` (optional)
- `fecha_hasta` (optional)
- `usuario_id` (optional)
- `evaluacion_id` (optional)
- `accion` (optional)
- `page` (optional)

Response `200`:
```json
{
  "data": [
    {
      "id": "uuid",
      "usuario_id": "uuid",
      "usuario_email": "investigador@example.com",
      "usuario_nombre": "Dra. Ana Ruiz",
      "evaluacion_id": "uuid",
      "accion": "crear_evaluacion",
      "timestamp": "2026-10-23T08:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "total": 15 }
}
```

#### GET `/api/v1/estadisticas/reclutamiento`
Returns recruitment metrics for the audit/statistics panel.

Query parameters:
- `fecha_desde` (required in UI, optional API — defaults to last 30 days)
- `fecha_hasta` (optional)
- `estado` (optional)

Response `200`:
```json
{
  "total_evaluaciones": 15,
  "elegibles": 9,
  "no_elegibles": 4,
  "revision": 2,
  "tasa_elegibilidad": 60.0,
  "distribucion_motivos_exclusion": [
    { "motivo": "Historial de tuberculosis", "cantidad": 2 },
    { "motivo": "Embarazo", "cantidad": 1 },
    { "motivo": "Uso previo de biológicos", "cantidad": 1 }
  ],
  "serie_temporal": [
    { "fecha": "2026-10-01", "evaluaciones": 5, "elegibles": 3 }
  ]
}
```

#### GET `/api/v1/auditoria/export.csv`
Exports filtered audit records as CSV.

Query parameters same as `GET /api/v1/auditoria`.

Response `200` with `Content-Type: text/csv` and `Content-Disposition: attachment; filename="auditoria_YYYYMMDD_HHmmss.csv"`.

CSV columns:
`id,usuario_id,usuario_email,usuario_nombre,evaluacion_id,accion,timestamp`

### 3.5 Error responses

All validation errors return `422` with:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "evaluacion.edad": ["El campo edad es obligatorio."]
  }
}
```

Unauthenticated requests return `401`; role-restricted actions return `403`.

---

## 4. FILE STRUCTURE

```
.
├── docker-compose.yml                          # Local orchestration: backend, frontend, mysql, redis
├── .gitignore                                  # Git ignore rules
├── .env.example                                # Root environment variable template
├── README.md                                   # Project overview and local setup
├── run.sh                                      # Startup script for local Docker compose
├── backend/
│   ├── Dockerfile                              # Backend image: PHP 8.3 + Nginx/FPM, EXPOSE 23080
│   ├── .env.example                            # Backend environment variable template
│   ├── composer.json                           # Laravel dependencies
│   ├── artisan                                 # Laravel CLI
│   ├── bootstrap/
│   │   ├── app.php                             # Laravel bootstrap
│   │   └── providers.php                       # Service providers registration
│   ├── config/
│   │   ├── app.php                             # Laravel app config
│   │   ├── auth.php                            # Auth guards (Passport + Cognito)
│   │   ├── database.php                        # MySQL connection
│   │   ├── cache.php                           # Redis cache
│   │   ├── session.php                         # Redis session
│   │   ├── logging.php                         # CloudWatch structured logs
│   │   ├── cors.php                            # CORS for frontend
│   │   ├── passport.php                        # Passport client config
│   │   ├── secrets.php                         # AWS Secrets Manager config
│   │   └── appconfig.php                       # AWS AppConfig config
│   ├── routes/
│   │   └── api.php                             # All /api/v1 routes with auth:api middleware
│   ├── public/
│   │   └── index.php                           # Laravel public entry
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 2026_01_01_000001_create_paciente_table.php
│   │   │   ├── 2026_01_01_000002_create_usuario_table.php
│   │   │   ├── 2026_01_01_000003_create_evaluacion_table.php
│   │   │   ├── 2026_01_01_000004_create_resultado_evaluacion_table.php
│   │   │   ├── 2026_01_01_000005_create_regla_criterio_table.php
│   │   │   ├── 2026_01_01_000006_create_evaluacion_criterio_table.php
│   │   │   └── 2026_01_01_000007_create_auditoria_table.php
│   │   └── seeders/
│   │       ├── DatabaseSeeder.php              # Calls ReglaCriterioSeeder, PerfilSinteticoSeeder
│   │       ├── ReglaCriterioSeeder.php         # Seeds 8 clinical criteria rules
│   │       └── PerfilSinteticoSeeder.php       # Seeds 15 synthetic patient profiles
│   └── app/
│       ├── Domain/
│       │   ├── Entities/
│       │   │   ├── Paciente.php                # PACIENTE domain entity
│       │   │   ├── Evaluacion.php              # EVALUACION domain entity
│       │   │   ├── ResultadoEvaluacion.php     # RESULTADO_EVALUACION domain entity
│       │   │   ├── ReglaCriterio.php           # REGLA_CRITERIO domain entity
│       │   │   ├── EvaluacionCriterio.php      # EVALUACION_CRITERIO domain entity
│       │   │   ├── Usuario.php                 # USUARIO domain entity
│       │   │   ├── Auditoria.php               # AUDITORIA domain entity
│       │   │   └── Enums/
│       │   │       ├── Rol.php                 # investigador, gestor
│       │   │       ├── Resultado.php           # elegible, no_elegible, revision
│       │   │       ├── EstadoCriterio.php      # pass, fail, na
│       │   │       └── TipoCriterio.php        # inclusion, exclusion
│       │   ├── Repositories/
│       │   │   ├── PacienteRepositoryInterface.php
│       │   │   ├── EvaluacionRepositoryInterface.php
│       │   │   ├── ResultadoEvaluacionRepositoryInterface.php
│       │   │   ├── ReglaCriterioRepositoryInterface.php
│       │   │   ├── EvaluacionCriterioRepositoryInterface.php
│       │   │   ├── UsuarioRepositoryInterface.php
│       │   │   └── AuditoriaRepositoryInterface.php
│       │   ├── Services/
│       │   │   ├── MotorElegibilidad.php       # Pure domain engine: evaluates criteria, returns result
│       │   │   ├── EvaluadorCriterio.php       # Evaluates a single REGLA_CRITERIO against EVALUACION
│       │   │   └── ResolvedorResultado.php     # Compose result from criterion states
│       │   └── Exceptions/
│       │       ├── PacienteNoEncontrado.php
│       │       └── EvaluacionInvalida.php
│       ├── Application/
│       │   ├── DTOs/
│       │   │   ├── PacienteDTO.php
│       │   │   ├── EvaluacionDTO.php
│       │   │   ├── ResultadoEvaluacionDTO.php
│       │   │   ├── ReglaCriterioDTO.php
│       │   │   ├── EvaluacionCriterioDTO.php
│       │   │   ├── UsuarioDTO.php
│       │   │   ├── AuditoriaDTO.php
│       │   │   ├── CrearEvaluacionRequest.php
│       │   │   ├── EvaluacionResolutionResponse.php
│       │   │   └── EstadisticasReclutamientoDTO.php
│       │   └── UseCases/
│       │       ├── EvaluarElegibilidad.php     # Orchestrates create/update patient, evaluation, engine, persistence, audit
│       │       ├── ConsultarEvaluacion.php     # Full resolution detail plus audit record
│       │       ├── ListarEvaluaciones.php      # Paginated evaluation list
│       │       ├── ListarCriterios.php         # Active criteria list
│       │       ├── ListarAuditoria.php         # Paginated audit list
│       │       ├── ConsultarEstadisticas.php   # Recruitment metrics
│       │       └── ExportarAuditoriaCsv.php    # Generate CSV from audit filters
│       └── Infrastructure/
│           ├── Persistence/Eloquent/
│           │   ├── Models/
│           │   │   ├── Paciente.php            # Eloquent PACIENTE model
│           │   │   ├── Evaluacion.php          # Eloquent EVALUACION model
│           │   │   ├── ResultadoEvaluacion.php # Eloquent RESULTADO_EVALUACION model
│           │   │   ├── ReglaCriterio.php       # Eloquent REGLA_CRITERIO model
│           │   │   ├── EvaluacionCriterio.php  # Eloquent EVALUACION_CRITERIO model
│           │   │   ├── Usuario.php             # Eloquent USUARIO model
│           │   │   └── Auditoria.php           # Eloquent AUDITORIA model
│           │   └── Repositories/
│           │       ├── EloquentPacienteRepository.php
│           │       ├── EloquentEvaluacionRepository.php
│           │       ├── EloquentResultadoEvaluacionRepository.php
│           │       ├── EloquentReglaCriterioRepository.php
│           │       ├── EloquentEvaluacionCriterioRepository.php
│           │       ├── EloquentUsuarioRepository.php
│           │       └── EloquentAuditoriaRepository.php
│           ├── Auth/
│           │   ├── CognitoJWTDecoder.php       # Decodes/validates Cognito JWT via JWKS
│           │   ├── CognitoUserProvider.php     # Resolves Cognito user to USUARIO
│           │   └── PassportCognitoGuard.php    # auth:api guard backed by Passport/Cognito
│           ├── AWS/
│           │   ├── SecretsManagerClient.php    # Fetches DB/credentials from Secrets Manager
│           │   ├── AppConfigClient.php         # Fetches dynamic config parameters from AppConfig
│           │   └── CloudWatchLogger.php        # Structured logger to CloudWatch Logs
│           └── Http/
│               ├── Controllers/
│               │   ├── EvaluacionController.php
│               │   ├── CriterioController.php
│               │   ├── AuditoriaController.php
│               │   ├── EstadisticasController.php
│               │   └── HealthController.php
│               ├── Middleware/
│               │   └── EnsureRol.php           # Role check: investigador, gestor
│               └── Requests/
│                   └── CrearEvaluacionRequest.php
├── frontend/
│   ├── Dockerfile                              # Frontend image: Node build + Nginx, EXPOSE 23081
│   ├── .env.example                            # Frontend environment variable template
│   ├── index.html                              # Vite HTML entry; script src="/src/main.tsx"
│   ├── package.json
│   ├── tsconfig.json
│   ├── vite.config.ts                          # Vite config, port 23081
│   └── src/
│       ├── main.tsx                            # React entry point
│       ├── App.tsx                             # App routing and layout
│       ├── api/
│       │   ├── client.ts                       # Axios/fetch wrapper with JWT header
│       │   ├── auth.ts                         # Cognito/Amplify auth helpers
│       │   ├── evaluaciones.ts                 # Evaluations API calls
│       │   ├── criterios.ts                    # Criteria API calls
│       │   ├── auditoria.ts                    # Audit API calls
│       │   └── estadisticas.ts                 # Statistics API calls
│       ├── types/
│       │   └── dto.ts                          # All DTO interfaces (mirror backend)
│       ├── hooks/
│       │   ├── useAuth.ts
│       │   ├── useEvaluacion.ts
│       │   ├── useAuditoria.ts
│       │   └── useEstadisticas.ts
│       ├── components/
│       │   ├── ui/
│       │   │   ├── NavegacionPrincipal.tsx
│       │   │   ├── BotonAccionPrincipal.tsx
│       │   │   ├── TarjetaClinica.tsx
│       │   │   ├── CampoFormulario.tsx
│       │   │   ├── CustomSelect.tsx            # Accessible custom select without native <select>
│       │   │   ├── InsigniaEstado.tsx
│       │   │   ├── ListaCriterios.tsx
│       │   │   ├── MetricaEstadistica.tsx
│       │   │   └── TablaAuditoria.tsx
│       │   └── layout/
│       │       └── PlantillaClinica.tsx        # Clinical layout wrapper
│       ├── pages/
│       │   ├── FormularioEvaluacion.tsx         # Page: Formulario de Evaluación
│       │   ├── PanelResolucionElegibilidad.tsx  # Page: Panel de Resolución de Elegibilidad
│       │   └── PanelAuditoriaEstadisticas.tsx  # Page: Panel de Auditoría y Estadísticas de Reclutamiento
│       ├── styles/
│       │   └── tokens.ts                       # Design tokens verbatim from UI/UX contract
│       └── utils/
│           ├── formatters.ts                   # Date, percent, status formatting
│           └── validation.ts                   # Clinical form validation helpers
└── infrastructure/
    ├── aws/
    │   ├── ecs/
    │   │   ├── task-definition.json            # ECS Fargate task definition for backend
    │   │   └── service.json                    # ECS service rolling update config
    │   └── cloudformation/
    │       └── main.yml                        # S3, CloudFront, ECS, RDS, ElastiCache, Cognito resources
    └── monitoring/
        └── cloudwatch/
            └── log-group-policy.json           # CloudWatch log group retention and policy
```

### Port table

| Service | Listening Port | Path |
|---|---|---|
| backend-api | 23080 | backend/ |
| frontend | 23081 | frontend/ |
| mysql (host mapped) | 23306:3306 | docker-compose service `mysql` |
| redis (host mapped) | 26379:6379 | docker-compose service `redis` |

Dockerfiles use the exact listening port in `EXPOSE` and `CMD`. Backend `EXPOSE 23080`, frontend nginx listens on `23081` in the container image.

---

## 5. ENVIRONMENT VARIABLES

### Backend (`backend/.env.example`)

| Name | Type | Description | Example |
|---|---|---|---|
| APP_ENV | string | Laravel environment | `production` |
| APP_KEY | string | Laravel encryption key | `base64:...` |
| APP_DEBUG | boolean | Debug mode | `false` |
| APP_URL | string | Backend public URL | `https://api.alm-pso2026.example.com` |
| APP_NAME | string | App name | `Motor de Pre-selección Clínica Etapa PO` |
| LOG_CHANNEL | string | Log channel | `cloudwatch` |
| LOG_LEVEL | string | Minimum log level | `info` |
| DB_CONNECTION | string | Database driver | `mysql` |
| DB_HOST | string | RDS endpoint from Secrets Manager | `motor-clinico.cluster-xxx.rds.amazonaws.com` |
| DB_PORT | integer | RDS port | `3306` |
| DB_DATABASE | string | Database name | `motor_clinico_psoriasis` |
| DB_USERNAME | string | DB username from Secrets Manager | `admin` |
| DB_PASSWORD | string | DB password from Secrets Manager | `secret` |
| REDIS_HOST | string | ElastiCache endpoint | `motor-clinico-cache.xxxxx.use1.cache.amazonaws.com` |
| REDIS_PORT | integer | Redis port | `6379` |
| REDIS_PASSWORD | string | Redis AUTH password | `null` |
| CACHE_DRIVER | string | Laravel cache driver | `redis` |
| SESSION_DRIVER | string | Session driver | `redis` |
| QUEUE_CONNECTION | string | Queue connection | `sync` |
| PASSPORT_CLIENT_ID | string | Passport client ID (if used) | `uuid` |
| PASSPORT_CLIENT_SECRET | string | Passport client secret | `secret` |
| AWS_REGION | string | AWS region | `us-east-1` |
| AWS_COGNITO_USER_POOL_ID | string | Cognito user pool ID | `us-east-1_xxxxxxxx` |
| AWS_COGNITO_CLIENT_ID | string | Cognito app client ID | `xxxxxxxxxxxxxxxx` |
| AWS_COGNITO_CLIENT_SECRET | string | Cognito app client secret | `secret` |
| AWS_COGNITO_JWKS_URL | string | JWKS endpoint for token validation | `https://cognito-idp.us-east-1.amazonaws.com/us-east-1_xxx/.well-known/jwks.json` |
| AWS_SECRETS_MANAGER_SECRET_ID | string | Secret ARN/name holding DB creds | `motor-clinico/backend/db` |
| APP_CONFIG_APPLICATION_ID | string | AppConfig application ID | `motor-clinico` |
| APP_CONFIG_ENVIRONMENT_ID | string | AppConfig environment ID | `desarrollo` |
| APP_CONFIG_CONFIGURATION_PROFILE_ID | string | AppConfig profile ID | `motor-clinico-perfil` |
| CLOUDWATCH_LOG_GROUP | string | CloudWatch log group | `motor-clinico-backend` |
| CLOUDWATCH_LOG_STREAM | string | CloudWatch log stream | `ecs-backend` |
| CORS_ALLOWED_ORIGINS | string | Comma-separated allowed origins | `https://app.alm-pso2026.example.com` |
| FRONTEND_URL | string | Frontend URL for CORS/redirects | `https://app.alm-pso2026.example.com` |
| EVALUACION_ENGINE_CACHE_TTL | integer | Seconds to cache evaluation results | `300` |
| APP_CONFIG_USE_FOR_RULES | boolean | Whether to load clinical rules from AppConfig | `false` |

### Frontend (`frontend/.env.example`)

| Name | Type | Description | Example |
|---|---|---|---|
| VITE_API_BASE_URL | string | BFF base URL | `https://api.alm-pso2026.example.com/api/v1` |
| VITE_AWS_REGION | string | AWS region | `us-east-1` |
| VITE_COGNITO_USER_POOL_ID | string | Cognito user pool ID | `us-east-1_xxxxxxxx` |
| VITE_COGNITO_CLIENT_ID | string | Cognito app client ID | `xxxxxxxxxxxxxxxx` |
| VITE_COGNITO_IDENTITY_POOL_ID | string | Cognito identity pool ID | `us-east-1:xxxxxxxx` |
| VITE_PORT | integer | Vite dev server port | `23081` |

### Root (`.env.example`)

| Name | Type | Description | Example |
|---|---|---|---|
| BACKEND_HOST_PORT | integer | Host port for backend | `23080` |
| FRONTEND_HOST_PORT | integer | Host port for frontend | `23081` |
| MYSQL_HOST_PORT | integer | Host port for MySQL | `23306` |
| REDIS_HOST_PORT | integer | Host port for Redis | `26379` |

---

## 6. IMPORT CONTRACTS

### Backend PHP import contracts

```php
use App\Domain\Services\MotorElegibilidad;             // MotorElegibilidad::evaluar(Evaluacion $evaluacion, array $criterios): Resultado
use App\Domain\Services\EvaluadorCriterio;             // EvaluadorCriterio::evaluar(ReglaCriterio $criterio, Evaluacion $evaluacion): EvaluacionCriterio
use App\Domain\Services\ResolvedorResultado;           // ResolvedorResultado::resolver(array $estados): string
use App\Application\UseCases\EvaluarElegibilidad;      // EvaluarElegibilidad::__invoke(CrearEvaluacionRequest $request, Usuario $usuario): EvaluacionResolutionResponse
use App\Application\UseCases\ConsultarEvaluacion;      // ConsultarEvaluacion::__invoke(string $evaluacionId): EvaluacionResolutionResponse
use App\Application\UseCases\ListarEvaluaciones;       // ListarEvaluaciones::__invoke(array $filtros): LengthAwarePaginator
use App\Application\UseCases\ListarCriterios;          // ListarCriterios::__invoke(array $filtros): Collection
use App\Application\UseCases\ListarAuditoria;          // ListarAuditoria::__invoke(array $filtros): LengthAwarePaginator
use App\Application\UseCases\ConsultarEstadisticas;    // ConsultarEstadisticas::__invoke(array $filtros): EstadisticasReclutamientoDTO
use App\Application\UseCases\ExportarAuditoriaCsv;     // ExportarAuditoriaCsv::__invoke(array $filtros): StreamedResponse
use App\Infrastructure\Auth\CognitoJWTDecoder;         // CognitoJWTDecoder::decode(string $token): array
use App\Infrastructure\Auth\CognitoUserProvider;       // CognitoUserProvider::retrieveById(string $id): ?Usuario
use App\Infrastructure\AWS\SecretsManagerClient;       // SecretsManagerClient::getSecret(string $secretId): array
use App\Infrastructure\AWS\AppConfigClient;            // AppConfigClient::getConfiguration(string $configuration): array
use App\Infrastructure\AWS\CloudWatchLogger;           // CloudWatchLogger::write(string $level, string $message, array $context): void
```

### Frontend TypeScript import contracts

```typescript
import { tokens } from '../styles/tokens';
import type { CrearEvaluacionRequest, EvaluacionResolutionResponse, EstadisticasReclutamientoDTO, FiltrosAuditoria, AuditoriaDTO } from '../../types/dto';
import { useAuth } from '../../hooks/useAuth';
import { useEvaluacion } from '../../hooks/useEvaluacion';
import { useAuditoria } from '../../hooks/useAuditoria';
import { useEstadisticas } from '../../hooks/useEstadisticas';
import { NavegacionPrincipal } from '../ui/NavegacionPrincipal';
import { BotonAccionPrincipal } from '../ui/BotonAccionPrincipal';
import { TarjetaClinica } from '../ui/TarjetaClinica';
import { CampoFormulario } from '../ui/CampoFormulario';
import { CustomSelect } from '../ui/CustomSelect';
import { InsigniaEstado } from '../ui/InsigniaEstado';
import { ListaCriterios } from '../ui/ListaCriterios';
import { MetricaEstadistica } from '../ui/MetricaEstadistica';
import { TablaAuditoria } from '../ui/TablaAuditoria';
import { PlantillaClinica } from '../layout/PlantillaClinica';
```

---

## 7. FRONTEND STATE & COMPONENT CONTRACTS

### 7.1 Hooks (shared state primitives)

```typescript
// useAuth.ts
export function useAuth(): {
  user: UsuarioDTO | null;
  loading: boolean;
  error: string | null;
  isAuthenticated: boolean;
  userRole: 'investigador' | 'gestor' | null;
  signIn: (email: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

// useEvaluacion.ts
export function useEvaluacion(evaluacionId?: string): {
  evaluacion: EvaluacionResolutionResponse | null;
  loading: boolean;
  error: string | null;
  crearEvaluacion: (request: CrearEvaluacionRequest) => Promise<EvaluacionResolutionResponse | null>;
  recargarEvaluacion: () => Promise<void>;
  exportarEvaluacionJson: () => Promise<void>;
};

// useAuditoria.ts
export function useAuditoria(filtrosIniciales?: FiltrosAuditoria): {
  auditorias: AuditoriaDTO[];
  filtros: FiltrosAuditoria;
  loading: boolean;
  error: string | null;
  setFiltros: (filtros: FiltrosAuditoria) => void;
  exportarCsv: () => Promise<void>;
};

// useEstadisticas.ts
export function useEstadisticas(filtrosIniciales?: FiltrosAuditoria): {
  estadisticas: EstadisticasReclutamientoDTO | null;
  filtros: FiltrosAuditoria;
  loading: boolean;
  error: string | null;
  actualizarFiltros: (filtros: FiltrosAuditoria) => void;
  recargarEstadisticas: () => Promise<void>;
};
```

### 7.2 Base component props interfaces

```typescript
// NavegacionPrincipal.tsx
export interface NavegacionPrincipalProps {
  activeItem: string;
  userName: string;
  userRole: 'investigador' | 'gestor';
  onNavigate: (item: string) => void;
  onLogout: () => void;
}

// BotonAccionPrincipal.tsx
export interface BotonAccionPrincipalProps {
  label: string;
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
  type?: 'button' | 'submit';
  disabled?: boolean;
  loading?: boolean;
  icon?: ReactNode;
  onClick: () => void;
}

// TarjetaClinica.tsx
export interface TarjetaClinicaProps {
  title?: string;
  children: ReactNode;
  padding?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'outline' | 'muted';
  testId?: string;
}

// CampoFormulario.tsx
export interface CampoFormularioProps {
  label: string;
  htmlFor: string;
  required?: boolean;
  error?: string;
  hint?: string;
  children: ReactNode;
}

// CustomSelect.tsx
export interface CustomSelectOption {
  value: string;
  label: string;
}
export interface CustomSelectProps {
  id: string;
  label: string;
  options: CustomSelectOption[];
  value: string | null;
  onChange: (value: string) => void;
  placeholder?: string;
  disabled?: boolean;
  error?: string;
  required?: boolean;
}

// InsigniaEstado.tsx
export type InsigniaEstadoStatus = 'elegible' | 'no_elegible' | 'revision' | 'pendiente';
export interface InsigniaEstadoProps {
  status: InsigniaEstadoStatus;
  size?: 'sm' | 'md';
  label?: string;
}

// ListaCriterios.tsx
export type EstadoCriterioUI = 'pass' | 'fail' | 'na';
export interface CriterioUI {
  id: string;
  label: string;
  status: EstadoCriterioUI;
  detail?: string;
}
export interface ListaCriteriosProps {
  title: string;
  criteria: CriterioUI[];
  open?: boolean;
  onToggle?: (criterionId: string) => void;
}

// MetricaEstadistica.tsx
export interface MetricaEstadisticaProps {
  label: string;
  value: string | number;
  delta?: string;
  trend?: 'up' | 'down' | 'neutral';
  tone?: 'default' | 'success' | 'error' | 'warning';
}

// TablaAuditoria.tsx
export type TablaAuditoriaRow = Record<string, string | number | ReactNode>;
export interface TablaAuditoriaColumn {
  key: string;
  header: string;
  align?: 'left' | 'right';
}
export interface TablaAuditoriaProps {
  rows: TablaAuditoriaRow[];
  columns: TablaAuditoriaColumn[];
  onRowClick: (row: TablaAuditoriaRow) => void;
  emptyStateLabel?: string;
}
```

The names declared here must be used verbatim across all consuming files.

---

## 8. FILE EXTENSION CONVENTION

- **Project type:** TypeScript (frontend), PHP (backend).
- **Frontend components/pages:** `.tsx` for all React components and pages. Never mix `.jsx` with TypeScript.
- **Frontend hooks/utils/types/styles:** `.ts` for non-JSX TypeScript files.
- **Entry point:** `frontend/src/main.tsx`, referenced in `frontend/index.html` as `<script type="module" src="/src/main.tsx"></script>`.
- **Backend:** `.php` for all PHP source files.

---

## 9. DESIGN TOKENS

`frontend/src/styles/tokens.ts` must export the following object. Values are copied verbatim from the UI/UX Design Implementation Contract.

```typescript
export const tokens = {
  colors: {
    primary: {
      main: '#1F5C8B',
      hover: '#174A70',
      pressed: '#123B59',
      focus: '#2E86C1',
    },
    secondary: {
      main: '#EAF2F8',
      hover: '#D8E7F1',
      text: '#1F5C8B',
    },
    neutral: {
      white: '#FFFFFF',
      background: '#F5F7FA',
      surface: '#FFFFFF',
      surface_muted: '#EDF1F5',
      border: '#D6DDE5',
      border_strong: '#B8C4D0',
      text_primary: '#17212B',
      text_secondary: '#4A5A6A',
      text_muted: '#6C7A89',
      disabled: '#9AA7B4',
    },
    semantic: {
      success: '#1E7B4F',
      success_surface: '#E8F5EE',
      error: '#B4232A',
      error_surface: '#FBEAEA',
      warning: '#9A6A00',
      warning_surface: '#FFF4D6',
      info: '#1F5C8B',
      info_surface: '#EAF2F8',
    },
    chart: {
      eligible: '#1E7B4F',
      not_eligible: '#B4232A',
      borderline: '#9A6A00',
      series_1: '#1F5C8B',
      series_2: '#5C9BC7',
      series_3: '#A8C6DD',
    },
  },
  typography: {
    font_family: 'Inter, system-ui, -apple-system, sans-serif',
    font_weights: {
      regular: 400,
      medium: 500,
      semibold: 600,
      bold: 700,
    },
    scale: {
      xs: { size: '12px', line_height: '16px', letter_spacing: '0.02em' },
      sm: { size: '13px', line_height: '18px', letter_spacing: '0.01em' },
      md: { size: '14px', line_height: '20px', letter_spacing: '0' },
      lg: { size: '16px', line_height: '24px', letter_spacing: '0' },
      xl: { size: '18px', line_height: '26px', letter_spacing: '-0.01em' },
      '2xl': { size: '22px', line_height: '30px', letter_spacing: '-0.01em' },
      '3xl': { size: '28px', line_height: '36px', letter_spacing: '-0.02em' },
    },
  },
  spacing: {
    0: '0px',
    1: '4px',
    2: '8px',
    3: '12px',
    4: '16px',
    5: '20px',
    6: '24px',
    7: '32px',
    8: '40px',
    9: '48px',
    10: '64px',
    11: '80px',
  },
  radii: {
    none: '0px',
    sm: '4px',
    md: '8px',
    lg: '12px',
    xl: '16px',
    pill: '999px',
  },
  shadows: {
    none: 'none',
    sm: '0 1px 2px rgba(23, 33, 43, 0.06)',
    md: '0 4px 12px rgba(23, 33, 43, 0.10)',
    lg: '0 12px 28px rgba(23, 33, 43, 0.14)',
    focus: '0 0 0 3px rgba(46, 134, 193, 0.28)',
  },
  icon_image_style: {
    icon_family: 'Line icons, outline style',
    stroke_width: '1.5px',
    size: '20px por defecto; 16px en tablas y badges',
    corner_style: 'redondeado 2px',
    photo_policy: 'Sin fotografías reales de pacientes; usar iniciales sobre fondo clínico neutro',
    charts: 'Líneas y barras planas, sin degradados, con leyenda clara',
  },
  motion: {
    duration_fast: '120ms',
    duration_base: '180ms',
    duration_slow: '240ms',
    easing: 'cubic-bezier(0.2, 0, 0.2, 1)',
    prefers_reduced_motion: 'Reducir animaciones a opacidad/color y eliminar desplazamientos',
    focus_behavior: 'Anillo visible, foco persistente, nunca solo color',
  },
  breakpoints: {
    mobile: '360px',
    tablet: '768px',
    desktop: '1280px',
    wide: '1600px',
  },
} as const;
```

All React components must import `tokens` from `../styles/tokens` and use token values instead of hardcoded hex or Tailwind defaults.

---

## 10. FUNCTIONAL REQUIREMENTS COVERAGE

| Requirement | Implementation | Files |
|---|---|---|
| Project named **Motor de Pre-selección Clínica Etapa PO** | Application name set in `APP_NAME`; repository documented with project name | `backend/.env.example`, `README.md`, `config/app.php` |
| Domain name **Motor Clínico Evaluación Pacientes Psoriasis** | Page footer, design system header, and app constants | `frontend/src/pages/*.tsx`, `frontend/src/styles/tokens.ts` |
| Protocol context **ALM-PSO-2026** | Visible in navigation and audit header | `NavegacionPrincipal.tsx`, `PanelAuditoriaEstadisticas.tsx` |
| Autonomía de negocio: no external hospital/payment/messaging integrations | Backend only integrates AWS, RDS, Redis, Cognito; no external business APIs | `backend/composer.json`, `backend/app/Infrastructure/AWS/*` |
| Strict DDD separation: Domain, Application, Infrastructure | `app/Domain`, `app/Application`, `app/Infrastructure` directories; domain has no framework imports | All `backend/app/Domain/**`, `backend/composer.json` |
| MySQL 8.0 with UUID primary keys; prohibit autoincrement IDs | Migrations use `char(36)` UUID v4/v7; no autoincrement | `database/migrations/*` |
| Entities exactly match ERD | Tables `PACIENTE`, `EVALUACION`, `RESULTADO_EVALUACION`, `REGLA_CRITERIO`, `EVALUACION_CRITERIO`, `USUARIO`, `AUDITORIA` with exact fields | `database/migrations/*`, `backend/app/Domain/Entities/*` |
| Relationship `USUARIO ||--o{ EVALUACION : crea` | Adds `usuario_id` FK to `EVALUACION` as implementation detail | `database/migrations/2026_01_01_000003_create_evaluacion_table.php` |
| Instant evaluation of psoriasis profiles | `MotorElegibilidad` pure domain service runs in request cycle; no queue | `backend/app/Domain/Services/MotorElegibilidad.php` |
| Eligibility result must be `elegible`, `no_elegible`, or `revision` | Enum `Resultado`; stored in `RESULTADO_EVALUACION.resultado` | `backend/app/Domain/Entities/Enums/Resultado.php`, migrations |
| Traceability detail for each decision | `RESULTADO_EVALUACION.detalle_trazabilidad` includes each criterion pass/fail detail | `MotorElegibilidad.php`, `ResolvedorResultado.php` |
| Criteria evaluation per rule | `EvaluadorCriterio` evaluates each active `REGLA_CRITERIO` against `EVALUACION`; saves `EVALUACION_CRITERIO` with `estado` and `motivo` | `backend/app/Domain/Services/EvaluadorCriterio.php`, `database/migrations/*` |
| Default clinical inclusion rules: edad ≥ 18, psoriasis_meses ≥ 6, PASI ≥ 10, BSA ≥ 10 | Seeded `REGLA_CRITERIO` rows | `database/seeders/ReglaCriterioSeeder.php` |
| Default clinical exclusion rules: historial_tb true, embarazo true, uso_biologicos_meses > 0, uso_corticoides_topicos_dias > 30 | Seeded `REGLA_CRITERIO` rows | `database/seeders/ReglaCriterioSeeder.php` |
| 15 synthetic profiles including Golden Path, false positives, borderline, single-criterion eliminations | Seeded patient/evaluation profiles | `database/seeders/PerfilSinteticoSeeder.php` |
| Frontend SPA in React + TypeScript | Vite React TypeScript project | `frontend/package.json`, `vite.config.ts`, `src/main.tsx` |
| CustomSelect without native `<select>` or external libraries | Accessible custom select component implementing keyboard navigation | `frontend/src/components/ui/CustomSelect.tsx` |
| Page: **Formulario de Evaluación** | Renders identification, demographic, inclusion/exclusion, and action sections | `frontend/src/pages/FormularioEvaluacion.tsx` |
| Page: **Panel de Resolución de Elegibilidad** | Renders summary, patient data, inclusion/exclusion breakdown, traceability actions | `frontend/src/pages/PanelResolucionElegibilidad.tsx` |
| Page: **Panel de Auditoría y Estadísticas de Reclutamiento** | Renders filters, metrics, exclusion distribution, recent evaluations, export actions | `frontend/src/pages/PanelAuditoriaEstadisticas.tsx` |
| UI base components exactly per contract | Nine components named as specified | `frontend/src/components/ui/*.tsx` |
| Design tokens verbatim | Single source tokens file | `frontend/src/styles/tokens.ts` |
| Cognito authentication via aws-amplify | `useAuth` uses Amplify Auth to sign in/out and obtains JWT | `frontend/src/hooks/useAuth.ts`, `frontend/src/api/auth.ts` |
| Laravel Passport `auth:api` validates JWT Bearer | `PassportCognitoGuard` and `CognitoJWTDecoder` validate Cognito tokens; routes use `auth:api` | `routes/api.php`, `backend/app/Infrastructure/Auth/*` |
| AWS Secrets Manager for credentials; no `.env` in production | `SecretsManagerClient` fetches DB credentials at boot; `.env.example` only | `backend/app/Infrastructure/AWS/SecretsManagerClient.php`, `config/secrets.php` |
| AWS AppConfig for dynamic parameters | `AppConfigClient` loads engine parameters and feature flags | `backend/app/Infrastructure/AWS/AppConfigClient.php`, `config/appconfig.php` |
| CloudWatch structured logs | `CloudWatchLogger` writes JSON structured logs with levels | `backend/app/Infrastructure/AWS/CloudWatchLogger.php`, `config/logging.php` |
| Amazon ElastiCache Redis for results/session cache | Cache driver `redis`, session driver `redis` | `config/cache.php`, `config/session.php` |
| Evaluation results cache TTL | `EVALUACION_ENGINE_CACHE_TTL` used when caching resolution | `backend/app/Application/UseCases/EvaluarElegibilidad.php` |
| Frontend hosted on S3 + CloudFront | Frontend build output deployed to S3; CloudFront distribution | `infrastructure/aws/cloudformation/main.yml`, `frontend/Dockerfile` |
| ECS/Fargate rolling deployment for backend | ECS task definition and service configured with Fargate and rolling update | `infrastructure/aws/ecs/task-definition.json`, `service.json` |
| RDS Multi-AZ backup and snapshots | CloudFormation resources specify Multi-AZ and backup retention | `infrastructure/aws/cloudformation/main.yml` |
| ElastiCache backup | CloudFormation ElastiCache replication/backup configuration | `infrastructure/aws/cloudformation/main.yml` |
| S3 versioning for frontend assets | CloudFormation S3 bucket with versioning enabled | `infrastructure/aws/cloudformation/main.yml` |
| Audit of every decision | `AUDITORIA` rows created on evaluation, resolution, export, and statistics actions | `backend/app/Application/UseCases/*`, `database/migrations/2026_01_01_000007_create_auditoria_table.php` |
| Role-based access: `investigador`, `gestor` | `EnsureRol` middleware; `USUARIO.rol` field | `backend/app/Infrastructure/Http/Middleware/EnsureRol.php` |
| Export audit CSV | `GET /api/v1/auditoria/export.csv` returns CSV | `backend/app/Application/UseCases/ExportarAuditoriaCsv.php`, `AuditoriaController.php` |
| Export evaluation JSON | `GET /api/v1/evaluaciones/{id}/export` returns JSON file | `backend/app/Application/UseCases/ConsultarEvaluacion.php` |
| Statistics of recruitment | `GET /api/v1/estadisticas/reclutamiento` returns totals, eligibility rate, exclusion distribution, time series | `backend/app/Application/UseCases/ConsultarEstadisticas.php`, `EstadisticasController.php` |
| Frontend TypeScript DTOs must mirror backend DTOs exactly | `frontend/src/types/dto.ts` names/types match PHP DTOs | `backend/app/Application/DTOs/*`, `frontend/src/types/dto.ts` |
| No autoincremental primary keys | UUID v7 generator used for all entities | `backend/app/Domain/Entities/*`, migrations |
| No native `<select>` in CustomSelect | CustomSelect uses button/listbox pattern with ARIA | `frontend/src/components/ui/CustomSelect.tsx` |
| Accessible focus visible, never color alone | Components use `tokens.shadows.focus` and `focus-visible` styles | `frontend/src/components/ui/*`, `tokens.ts` |
| No real patient photos; use initials on neutral clinical background | Avatar/initial pattern implemented in navigation and user contexts | `NavegacionPrincipal.tsx`, `PlantillaClinica.tsx` |
| Flat charts without gradients; clear legend | Statistics panel uses token chart colors and simple bar/line rendering | `PanelAuditoriaEstadisticas.tsx`, `MetricaEstadistica.tsx` |
| Motion with reduced-motion support | CSS/JS respects `prefers-reduced-motion`; uses opacity/color transitions | `frontend/src/styles/tokens.ts`, component styles |
| Backend health check for ECS | `GET /api/v1/health` | `backend/app/Infrastructure/Http/Controllers/HealthController.php`, `routes/api.php` |
| Minimum 80% test coverage with Pest PHP and `arch()` test | **Not implemented in this generated scope**: tests are outside the user-approved scope. The constraint is documented; no test files are generated. | — |
| Production credentials not in `.env` files | `.env.example` contains placeholders only; runtime credentials fetched from Secrets Manager | `backend/.env.example`, `config/secrets.php` |
| Structured error logging | CloudWatch logger integrated with Laravel exception handler | `backend/app/Infrastructure/AWS/CloudWatchLogger.php`, `bootstrap/app.php` |

---

No additional databases, caches, dashboards, tracing systems, or infrastructure layers beyond those explicitly required by the project context are included.