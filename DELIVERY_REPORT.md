# Delivery Report

**Outcome:** `partial`

## Completion metrics

- Implementation files: 63
- Expected implementation files: 132
- Blocking items: 13/29 done
- Failed items: 16
- Requirements: not measured
- Fidelity: not measured
- Abort reason: none

## Outstanding findings

- relocated: MotorElegibilidad.php->backend/app/Domain/Services/MotorElegibilidad.php
- relocated: database/seeders/ReglaCriterioSeeder.php->backend/database/seeders/ReglaCriterioSeeder.php
- relocated: database/seeders/PerfilSinteticoSeeder.php->backend/database/seeders/PerfilSinteticoSeeder.php
- relocated: config/secrets.php->backend/config/secrets.php
- relocated: config/appconfig.php->backend/config/appconfig.php
- relocated: config/logging.php->backend/config/logging.php
- relocated: config/cache.php->backend/config/cache.php
- relocated: config/session.php->backend/config/session.php
- relocated: database/migrations/2026_01_01_000007_create_auditoria_table.php->backend/database/migrations/2026_01_01_000007_create_auditoria_table.php
- relocated: tokens.ts->frontend/src/styles/tokens.ts
- Fidelity requirement not verifiable (no repair item created): TC002: Verificar el envío de datos de un paciente que cumple todos los criterios de elegibilidad. → El sistema muestra el estado 'Elegible' de forma clara e inmediata y un desglose que indica que todos los criterios de inclusión se cumplieron y ningún criterio de exclusión bloqueó la elegibilidad.
- Fidelity requirement not verifiable (no repair item created): TC003: Verificar que el formulario valida los campos obligatorios antes del envío. → El sistema muestra un mensaje claro indicando que los campos obligatorios deben ser completados y no permite el envío del formulario.
- Fidelity requirement not verifiable (no repair item created): TC004: Verificar la validación de rango para el campo 'Edad' (menor a 18). → El sistema muestra un mensaje de validación claro indicando que la 'edad debe ser mayor o igual a 18' y no permite el envío del formulario.
- Fidelity requirement not verifiable (no repair item created): TC005: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por embarazo. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'embarazo' bloqueó la elegibilidad.
- Fidelity requirement not verifiable (no repair item created): TC006: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por PASI < 10. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'PASI >= 10' no se cumplió.
- Fidelity requirement not verifiable (no repair item created): TC007: Verificar que el motor evalúa correctamente los casos límite para los criterios de inclusión. → El sistema muestra el estado 'Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que todos los criterios de inclusión, incluyendo los valores límite, se cumplieron.
- Fidelity requirement not verifiable (no repair item created): TC008: Verificar que todos los selectores del formulario utilizan el componente CustomSelect interno. → Todos los componentes de selección de opciones están implementados utilizando el estándar interno CustomSelect.
- Fidelity requirement not verifiable (no repair item created): TC009: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por historial de TB. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'historial de TB' bloqueó la elegibilidad.
- Fidelity requirement not verifiable (no repair item created): TC010: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por uso reciente de biológicos. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'biológicos en los últimos 90 días' bloqueó la elegibil…
- Fidelity requirement not verifiable (no repair item created): TC011: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por uso reciente de corticoides tópicos. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'corticoides en los últimos 14 días' bloqueó l…
- Fidelity requirement not verifiable (no repair item created): TC012: Verificar la validación de rango para el campo 'PASI' (valor no válido). → El sistema muestra un mensaje de validación claro indicando que el 'PASI debe estar en un rango válido' (e.g., entre 0 y 100) y no permite el envío del formulario.
- Fidelity requirement not verifiable (no repair item created): TC013: Verificar que se muestra un estado de carga durante el proceso de evaluación. → La interfaz de usuario muestra un indicador visual de carga (e.g., spinner, mensaje 'Evaluando...') mientras el sistema procesa la solicitud.
- Fidelity requirement not verifiable (no repair item created): TC014: Verificar el manejo de errores si el motor de pre-screening falla durante la comunicación. → El sistema muestra un mensaje de error claro al usuario (e.g., 'Error al evaluar la elegibilidad. Intente de nuevo más tarde.') y no muestra un resultado de elegibilidad.
- Fidelity requirement not verifiable (no repair item created): TC015: Verificar que el gestor del ensayo puede auditar las decisiones del sistema. → El sistema permite al gestor del ensayo ver el historial de evaluaciones de pacientes, incluyendo la decisión final 'Elegible'/'No Elegible' y el desglose de todos los criterios evaluados, tanto cumplidos como blo…
- Fidelity requirement not verifiable (no repair item created): TC016: Verificar la validación de rango para el campo 'BSA' (valor no válido). → El sistema muestra un mensaje de validación claro indicando que el 'BSA debe estar en un rango válido' y no permite el envío del formulario.
- Fidelity requirement not verifiable (no repair item created): TC017: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por diagnóstico de Psoriasis < 6 meses. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'diagnóstico crónico >= 6 meses' no se cumplió.
- Fidelity requirement not verifiable (no repair item created): TC018: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por BSA < 10%. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'BSA >= 10%' no se cumplió.
- Fidelity requirement not verifiable (no repair item created): TC019: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por múltiples criterios de exclusión. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que los criterios 'historial de TB' y 'embarazo' bloquearon la el…
- Fidelity requirement not verifiable (no repair item created): TC020: Verificar que el panel de auditoría muestra estadísticas de reclutamiento. → El sistema muestra estadísticas de reclutamiento, incluyendo el número de pacientes 'aprobados', 'rechazados' y los 'motivos' principales de rechazo.
- Fidelity requirement not verifiable (no repair item created): TC021: Verificar que un Investigador Clínico puede iniciar sesión exitosamente. → El sistema autentica al usuario y redirige al Investigador Clínico al formulario de evaluación de pacientes.
- Fidelity requirement not verifiable (no repair item created): TC022: Verificar que el generador de datos sintéticos puede crear un perfil 'Golden Path' elegible. → El sistema genera un perfil de paciente ficticio que cumple con todos los criterios de inclusión y ningún criterio de exclusión, resultando en un paciente 'Elegible'.
- Fidelity requirement not verifiable (no repair item created): NFR001: Verificar tiempo de respuesta del motor de elegibilidad. → El resultado de elegibilidad ("Elegible" o "No Elegible") y su trazabilidad se muestra de forma instantánea e inmediata (ej. < 2 segundos).
- Fidelity requirement not verifiable (no repair item created): NFR002: Evaluar el rendimiento del sistema bajo carga de usuarios concurrentes. → El sistema debe escalar automáticamente mediante ECS/Fargate, manteniendo una latencia de respuesta aceptable (ej. < 5 segundos) y sin errores de servicio.
- Fidelity requirement not verifiable (no repair item created): NFR003: Verificar que las credenciales no se exponen en archivos .env en producción. → No se encuentran archivos `.env` ni credenciales sensibles hardcodeadas en el entorno de producción. Todas las credenciales se cargan desde AWS Secrets Manager.
- Fidelity requirement not verifiable (no repair item created): NFR004: Validar la autenticación y autorización de usuarios mediante JWT y Laravel Passport. → 1. Acceso denegado con código de estado 401 (Unauthorized). 2. Acceso permitido a la ruta protegida. 3. Acceso denegado con código de estado 401 (Unauthorized).
- Fidelity requirement not verifiable (no repair item created): NFR005: Verificar la alta disponibilidad de la base de datos MySQL en un escenario de falla. → La base de datos realiza una conmutación por error automática y exitosa a la instancia secundaria en el tiempo esperado (ej. < 60 segundos), y el servicio del backend se recupera sin pérdida de datos ni i…
- Fidelity requirement not verifiable (no repair item created): NFR006: Validar que los errores del sistema se registran correctamente en AWS CloudWatch. → El error provocado se registra en AWS CloudWatch con el nivel de severidad correcto y detalles estructurados que permiten su análisis y trazabilidad.
- Fidelity requirement not verifiable (no repair item created): NFR007: Evaluar la efectividad de Amazon ElastiCache Redis para el almacenamiento en caché. → La segunda solicitud tiene un tiempo de respuesta significativamente menor que la primera, indicando que la respuesta fue servida desde ElastiCache Redis. La carga en la base de datos MySQL para la segunda…
- Fidelity requirement not verifiable (no repair item created): NFR008: Verificar que solo se utilizan datos sintéticos y no PHI real. → El sistema solo procesa y almacena los 15 perfiles de pacientes ficticios, sin ninguna exposición de PHI real, cumpliendo con la "validación estricta del dataset para evitar violaciones de privacidad".
- Fidelity requirement not verifiable (no repair item created): NFR009: Comprobar el comportamiento del sistema ante la indisponibilidad de AWS Secrets Manager. → El backend debe registrar un error claro sobre la imposibilidad de obtener credenciales de Secrets Manager y no debe exponer información sensible. El servicio debe fallar de forma controlada o no inic…
- Fidelity requirement not verifiable (no repair item created): NFR010: Comprobar el comportamiento del sistema ante la indisponibilidad de AWS AppConfig. → El backend debe registrar un error claro sobre la imposibilidad de obtener parámetros de AppConfig y no debe exponer información sensible. El servicio debe fallar de forma controlada o no iniciar.
- Fidelity requirement not verifiable (no repair item created): NFR011: Validar el rendimiento y accesibilidad del componente CustomSelect. → El CustomSelect funciona de manera fluida, es completamente accesible mediante teclado y no utiliza la etiqueta `<select>` nativa de HTML ni librerías externas, cumpliendo con el estándar interno.
- Fidelity requirement not verifiable (no repair item created): NFR013: Verificar el cifrado de datos en tránsito entre frontend y backend. → Toda la comunicación entre el frontend (S3/CloudFront) y el backend (ECS/Fargate) se realiza exclusivamente a través de HTTPS/TLS, asegurando el cifrado de los datos en tránsito.
- Fidelity requirement not verifiable (no repair item created): NFR014: Validar el escalado automático de instancias de backend en ECS/Fargate. → El servicio ECS/Fargate escala automáticamente, añadiendo nuevas tareas cuando los umbrales de CPU o peticiones son excedidos, y reduciendo las tareas cuando la carga disminuye, manteniendo la disponibilidad y el rend…
- Fidelity requirement not verifiable (no repair item created): NFR015: Verificar coherencia de contratos DTOs entre Backend y Frontend. → Las interfaces TypeScript del frontend son exactamente idénticas a los DTOs del backend, garantizando la coherencia de los contratos.
- Fidelity requirement not verifiable (no repair item created): NFR016: Validar que la cobertura de pruebas del backend cumple con el mínimo requerido. → La cobertura de pruebas en las capas de Dominio y Aplicación del backend es al menos del 80%, según lo especificado.
- Fidelity requirement not verifiable (no repair item created): NFR017: Comprobar que la arquitectura del backend previene la filtración de infraestructura al dominio. → El test `arch()` de Pest PHP se ejecuta exitosamente, confirmando que la infraestructura no se filtra al dominio.
- Fidelity requirement not verifiable (no repair item created): NFR018: Verificar la capacidad de recuperar la base de datos MySQL a partir de snapshots automáticos. → La base de datos RDS MySQL se recupera exitosamente a partir de un snapshot automático, sin pérdida de datos crítica y permitiendo la operación normal de la aplicación.
- Fidelity requirement not verifiable (no repair item created): Domain name **Motor Clínico Evaluación Pacientes Psoriasis**
- Fidelity requirement not verifiable (no repair item created): Autonomía de negocio: no external hospital/payment/messaging integrations
- Fidelity requirement not verifiable (no repair item created): Strict DDD separation: Domain, Application, Infrastructure
- Fidelity requirement not verifiable (no repair item created): MySQL 8.0 with UUID primary keys; prohibit autoincrement IDs
- Fidelity requirement not verifiable (no repair item created): Entities exactly match ERD
- Fidelity requirement not verifiable (no repair item created): Instant evaluation of psoriasis profiles
- Fidelity requirement not verifiable (no repair item created): Criteria evaluation per rule
- Fidelity requirement not verifiable (no repair item created): Default clinical inclusion rules: edad ≥ 18, psoriasis_meses ≥ 6, PASI ≥ 10, BSA ≥ 10
- Fidelity requirement not verifiable (no repair item created): Default clinical exclusion rules: historial_tb true, embarazo true, uso_biologicos_meses > 0, uso_corticoides_topicos_dias > 30
- Fidelity requirement not verifiable (no repair item created): 15 synthetic profiles including Golden Path, false positives, borderline, single-criterion eliminations
- Fidelity requirement not verifiable (no repair item created): CustomSelect without native `<select>` or external libraries
- Fidelity requirement not verifiable (no repair item created): Page: **Formulario de Evaluación**
- Fidelity requirement not verifiable (no repair item created): Page: **Panel de Resolución de Elegibilidad**
- Fidelity requirement not verifiable (no repair item created): Page: **Panel de Auditoría y Estadísticas de Reclutamiento**
- Fidelity requirement not verifiable (no repair item created): UI base components exactly per contract
- Fidelity requirement not verifiable (no repair item created): Design tokens verbatim
- Fidelity requirement not verifiable (no repair item created): Laravel Passport `auth:api` validates JWT Bearer
- Fidelity requirement not verifiable (no repair item created): AWS Secrets Manager for credentials; no `.env` in production
- Fidelity requirement not verifiable (no repair item created): AWS AppConfig for dynamic parameters
- Fidelity requirement not verifiable (no repair item created): CloudWatch structured logs
- Fidelity requirement not verifiable (no repair item created): Amazon ElastiCache Redis for results/session cache
- Fidelity requirement not verifiable (no repair item created): Evaluation results cache TTL
- Fidelity requirement not verifiable (no repair item created): Frontend hosted on S3 + CloudFront
- Fidelity requirement not verifiable (no repair item created): ECS/Fargate rolling deployment for backend
- Fidelity requirement not verifiable (no repair item created): RDS Multi-AZ backup and snapshots
- Fidelity requirement not verifiable (no repair item created): ElastiCache backup
- Fidelity requirement not verifiable (no repair item created): S3 versioning for frontend assets
- Fidelity requirement not verifiable (no repair item created): Audit of every decision
- Fidelity requirement not verifiable (no repair item created): Role-based access: `investigador`, `gestor`
- Fidelity requirement not verifiable (no repair item created): Export audit CSV
- Fidelity requirement not verifiable (no repair item created): Export evaluation JSON
- Fidelity requirement not verifiable (no repair item created): Statistics of recruitment
- Fidelity requirement not verifiable (no repair item created): Frontend TypeScript DTOs must mirror backend DTOs exactly
- Fidelity requirement not verifiable (no repair item created): No native `<select>` in CustomSelect
- Fidelity requirement not verifiable (no repair item created): Accessible focus visible, never color alone
- Fidelity requirement not verifiable (no repair item created): No real patient photos; use initials on neutral clinical background
- Fidelity requirement not verifiable (no repair item created): Flat charts without gradients; clear legend
- Fidelity requirement not verifiable (no repair item created): Motion with reduced-motion support
- Fidelity requirement not verifiable (no repair item created): Backend health check for ECS
- Fidelity requirement not verifiable (no repair item created): Production credentials not in `.env` files
- Fidelity requirement not verifiable (no repair item created): Structured error logging
- Fidelity verifier unavailable: Fidelity verifier unavailable: 
- Ítem no completado: Backend — Motor de Elegibilidad y Creación de Evaluación (2/5)
- Ítem no completado: Backend — Motor de Elegibilidad y Creación de Evaluación (3/5)
- Ítem no completado: Backend — Motor de Elegibilidad y Creación de Evaluación (4/5)
- Ítem no completado: Backend — Motor de Elegibilidad y Creación de Evaluación (5/5)
- Ítem no completado: Backend — Consulta y Listado de Evaluaciones y Criterios
- Ítem no completado: Backend — Auditoría y Estadísticas (1/2)
- Ítem no completado: Backend — Auditoría y Estadísticas (2/2)
- Ítem no completado: Frontend — Autenticación y Estructura de la Aplicación (2/2)
- Ítem no completado: Frontend — Formulario de Evaluación de Pacientes (1/2)
- Ítem no completado: Frontend — Formulario de Evaluación de Pacientes (2/2)
- Ítem no completado: Frontend — Panel de Resolución de Elegibilidad
- Ítem no completado: Frontend — Panel de Auditoría y Estadísticas
- Ítem no completado: Dockerización del Backend
- Ítem no completado: Dockerización del Frontend
- Ítem no completado: Infraestructura y Despliegue Local
- Ítem no completado: Fidelity verification unavailable
- [project] Functional requirement not implemented: **Project named **Motor de Pre-selección Clínica Etapa PO****
Implementation approach: Application name set in `APP_NAME`; repository documented with project name
Missing file(s) that must be created/completed:
  - `backend/.env.example`
  - `README.md`
  - `config/app.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Domain name **Motor Clínico Evaluación Pacientes Psoriasis****
Implementation approach: Page footer, design system header, and app constants
Missing file(s) that must be created/completed:
  - `frontend/src/pages/*.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Protocol context **ALM-PSO-2026****
Implementation approach: Visible in navigation and audit header
Missing file(s) that must be created/completed:
  - `NavegacionPrincipal.tsx`
  - `PanelAuditoriaEstadisticas.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Strict DDD separation: Domain, Application, Infrastructure**
Implementation approach: `app/Domain`, `app/Application`, `app/Infrastructure` directories; domain has no framework imports
Missing file(s) that must be created/completed:
  - `All `backend/app/Domain/**`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **MySQL 8.0 with UUID primary keys; prohibit autoincrement IDs**
Implementation approach: Migrations use `char(36)` UUID v4/v7; no autoincrement
Missing file(s) that must be created/completed:
  - `database/migrations/*`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Entities exactly match ERD**
Implementation approach: Tables `PACIENTE`, `EVALUACION`, `RESULTADO_EVALUACION`, `REGLA_CRITERIO`, `EVALUACION_CRITERIO`, `USUARIO`, `AUDITORIA` with exact fields
Missing file(s) that must be created/completed:
  - `database/migrations/*`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Traceability detail for each decision**
Implementation approach: `RESULTADO_EVALUACION.detalle_trazabilidad` includes each criterion pass/fail detail
Missing file(s) that must be created/completed:
  - `ResolvedorResultado.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Criteria evaluation per rule**
Implementation approach: `EvaluadorCriterio` evaluates each active `REGLA_CRITERIO` against `EVALUACION`; saves `EVALUACION_CRITERIO` with `estado` and `motivo`
Missing file(s) that must be created/completed:
  - `database/migrations/*`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Frontend SPA in React + TypeScript**
Implementation approach: Vite React TypeScript project
Missing file(s) that must be created/completed:
  - `vite.config.ts`
  - `src/main.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **CustomSelect without native `<select>` or external libraries**
Implementation approach: Accessible custom select component implementing keyboard navigation
Missing file(s) that must be created/completed:
  - `frontend/src/components/ui/CustomSelect.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Page: **Formulario de Evaluación****
Implementation approach: Renders identification, demographic, inclusion/exclusion, and action sections
Missing file(s) that must be created/completed:
  - `frontend/src/pages/FormularioEvaluacion.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Page: **Panel de Resolución de Elegibilidad****
Implementation approach: Renders summary, patient data, inclusion/exclusion breakdown, traceability actions
Missing file(s) that must be created/completed:
  - `frontend/src/pages/PanelResolucionElegibilidad.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Page: **Panel de Auditoría y Estadísticas de Reclutamiento****
Implementation approach: Renders filters, metrics, exclusion distribution, recent evaluations, export actions
Missing file(s) that must be created/completed:
  - `frontend/src/pages/PanelAuditoriaEstadisticas.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **UI base components exactly per contract**
Implementation approach: Nine components named as specified
Missing file(s) that must be created/completed:
  - `frontend/src/components/ui/*.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Cognito authentication via aws-amplify**
Implementation approach: `useAuth` uses Amplify Auth to sign in/out and obtains JWT
Missing file(s) that must be created/completed:
  - `frontend/src/hooks/useAuth.ts`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Laravel Passport `auth:api` validates JWT Bearer**
Implementation approach: `PassportCognitoGuard` and `CognitoJWTDecoder` validate Cognito tokens; routes use `auth:api`
Missing file(s) that must be created/completed:
  - `routes/api.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Evaluation results cache TTL**
Implementation approach: `EVALUACION_ENGINE_CACHE_TTL` used when caching resolution
Missing file(s) that must be created/completed:
  - `backend/app/Application/UseCases/EvaluarElegibilidad.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Frontend hosted on S3 + CloudFront**
Implementation approach: Frontend build output deployed to S3; CloudFront distribution
Missing file(s) that must be created/completed:
  - `infrastructure/aws/cloudformation/main.yml`
  - `frontend/Dockerfile`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **ECS/Fargate rolling deployment for backend**
Implementation approach: ECS task definition and service configured with Fargate and rolling update
Missing file(s) that must be created/completed:
  - `infrastructure/aws/ecs/task-definition.json`
  - `service.json`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **RDS Multi-AZ backup and snapshots**
Implementation approach: CloudFormation resources specify Multi-AZ and backup retention
Missing file(s) that must be created/completed:
  - `infrastructure/aws/cloudformation/main.yml`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **ElastiCache backup**
Implementation approach: CloudFormation ElastiCache replication/backup configuration
Missing file(s) that must be created/completed:
  - `infrastructure/aws/cloudformation/main.yml`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **S3 versioning for frontend assets**
Implementation approach: CloudFormation S3 bucket with versioning enabled
Missing file(s) that must be created/completed:
  - `infrastructure/aws/cloudformation/main.yml`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Audit of every decision**
Implementation approach: `AUDITORIA` rows created on evaluation, resolution, export, and statistics actions
Missing file(s) that must be created/completed:
  - `backend/app/Application/UseCases/*`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Role-based access: `investigador`, `gestor`**
Implementation approach: `EnsureRol` middleware; `USUARIO.rol` field
Missing file(s) that must be created/completed:
  - `backend/app/Infrastructure/Http/Middleware/EnsureRol.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Export audit CSV**
Implementation approach: `GET /api/v1/auditoria/export.csv` returns CSV
Missing file(s) that must be created/completed:
  - `backend/app/Application/UseCases/ExportarAuditoriaCsv.php`
  - `AuditoriaController.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Export evaluation JSON**
Implementation approach: `GET /api/v1/evaluaciones/{id}/export` returns JSON file
Missing file(s) that must be created/completed:
  - `backend/app/Application/UseCases/ConsultarEvaluacion.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Statistics of recruitment**
Implementation approach: `GET /api/v1/estadisticas/reclutamiento` returns totals, eligibility rate, exclusion distribution, time series
Missing file(s) that must be created/completed:
  - `backend/app/Application/UseCases/ConsultarEstadisticas.php`
  - `EstadisticasController.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **No native `<select>` in CustomSelect**
Implementation approach: CustomSelect uses button/listbox pattern with ARIA
Missing file(s) that must be created/completed:
  - `frontend/src/components/ui/CustomSelect.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Accessible focus visible, never color alone**
Implementation approach: Components use `tokens.shadows.focus` and `focus-visible` styles
Missing file(s) that must be created/completed:
  - `frontend/src/components/ui/*`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **No real patient photos; use initials on neutral clinical background**
Implementation approach: Avatar/initial pattern implemented in navigation and user contexts
Missing file(s) that must be created/completed:
  - `NavegacionPrincipal.tsx`
  - `PlantillaClinica.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Flat charts without gradients; clear legend**
Implementation approach: Statistics panel uses token chart colors and simple bar/line rendering
Missing file(s) that must be created/completed:
  - `PanelAuditoriaEstadisticas.tsx`
  - `MetricaEstadistica.tsx`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Backend health check for ECS**
Implementation approach: `GET /api/v1/health`
Missing file(s) that must be created/completed:
  - `backend/app/Infrastructure/Http/Controllers/HealthController.php`
  - `routes/api.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Production credentials not in `.env` files**
Implementation approach: `.env.example` contains placeholders only; runtime credentials fetched from Secrets Manager
Missing file(s) that must be created/completed:
  - `backend/.env.example`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
- [project] Functional requirement not implemented: **Structured error logging**
Implementation approach: CloudWatch logger integrated with Laravel exception handler
Missing file(s) that must be created/completed:
  - `bootstrap/app.php`

Instructions:
1. Create each missing file with complete, production-ready implementation.
2. Wire it into the service that owns it (add imports, register routes, etc.).
3. Do NOT create stub or placeholder files — implement the full logic.
4. Verify the file exists on disk after writing.
