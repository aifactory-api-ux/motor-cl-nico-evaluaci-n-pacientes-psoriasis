Hola, soy Developer. Implementaré las funcionalidades del proyecto siguiendo la arquitectura definida, con código limpio y testeado.

# DEVELOPMENT PLAN: Motor de Pre-selección Clínica Etapa PO

## 1. ARCHITECTURE OVERVIEW
El proyecto "Motor de Pre-selección Clínica Etapa PO" se desarrollará como una aplicación full-stack, siguiendo una arquitectura de microservicios monolito (monorepo) con una clara separación de capas (Dominio, Aplicación, Infraestructura) en el backend.

**Backend (PHP 8.3, Laravel 11):**
*   **Dominio:** Contiene las entidades (`Paciente`, `Evaluacion`, `ResultadoEvaluacion`, `ReglaCriterio`, `EvaluacionCriterio`, `Usuario`, `Auditoria`), enums (`Rol`, `Resultado`, `EstadoCriterio`, `TipoCriterio`), interfaces de repositorios y servicios puros de negocio (`MotorElegibilidad`, `EvaluadorCriterio`, `ResolvedorResultado`). No tiene dependencias de infraestructura.
*   **Aplicación:** Define los DTOs y los casos de uso (`EvaluarElegibilidad`, `ConsultarEvaluacion`, `ListarEvaluaciones`, `ListarCriterios`, `ListarAuditoria`, `ConsultarEstadisticas`, `ExportarAuditoriaCsv`) que orquestan las operaciones del dominio y la persistencia.
*   **Infraestructura:** Implementa los repositorios con Eloquent, controladores HTTP, middleware de autenticación/autorización (Laravel Passport con Cognito JWT), clientes para servicios AWS (Secrets Manager, AppConfig, CloudWatch Logger) y la configuración de la base de datos (MySQL 8.0), caché/sesiones (Redis) y logging.
*   **API Endpoints:** Todas las rutas bajo `/api/v1` están protegidas por `auth:api` (Laravel Passport + Cognito). Incluye endpoints para crear/consultar evaluaciones, listar criterios, consultar auditoría y estadísticas, y exportar datos.

**Frontend (React 18, TypeScript 5, Vite):**
*   **Estructura:** Aplicación de una sola página (SPA) con React Router para la navegación.
*   **Autenticación:** Integración con AWS Amplify para la autenticación de usuarios vía Amazon Cognito.
*   **Componentes:** Implementación de componentes UI/UX base (`NavegacionPrincipal`, `BotonAccionPrincipal`, `TarjetaClinica`, `CampoFormulario`, `CustomSelect`, `InsigniaEstado`, `ListaCriterios`, `MetricaEstadistica`, `TablaAuditoria`) siguiendo el contrato de diseño.
*   **Páginas:** Tres páginas principales: `FormularioEvaluacion`, `PanelResolucionElegibilidad`, `PanelAuditoriaEstadisticas`.
*   **Estado:** Gestión del estado con hooks personalizados (`useAuth`, `useEvaluacion`, `useAuditoria`, `useEstadisticas`).
*   **Diseño:** Uso estricto de los `Design Tokens` definidos en `frontend/src/styles/tokens.ts`.

**Infraestructura Local (Docker, docker-compose):**
*   **Contenedores:** Servicios de backend (PHP-FPM + Nginx), frontend (Node.js build + Nginx), MySQL y Redis.
*   **Orquestación:** `docker-compose.yml` para levantar y gestionar los servicios, con `healthchecks` y `depends_on: service_healthy`.
*   **Scripts:** `run.sh` para automatizar el inicio, incluyendo la espera por la base de datos, migraciones y siembra de datos.
*   **Variables de Entorno:** Gestión de configuración a través de `.env.example` y variables de entorno.

**Servicios AWS:**
*   **Autenticación:** Amazon Cognito (User Pool).
*   **Base de Datos:** Amazon RDS for MySQL 8.0 (con UUIDs v7 como PKs).
*   **Caché/Sesiones:** Amazon ElastiCache for Redis.
*   **Configuración Dinámica:** AWS AppConfig.
*   **Credenciales Seguras:** AWS Secrets Manager.
*   **Logging:** AWS CloudWatch (logs estructurados).
*   **Hosting Frontend:** Amazon S3 + Amazon CloudFront.
*   **Orquestación Backend:** Amazon ECS / AWS Fargate.

El proyecto se adhiere estrictamente a los contratos de `SPEC.md` para DTOs, endpoints, estructura de archivos y convenciones de importación.

## 2. ACCEPTANCE CRITERIA
1. TC001: Verificar la creación de un nuevo proyecto con el nombre especificado. → El sistema crea un proyecto nuevo con el nombre 'Motor de Pre-selección Clínica Etapa PO'.
2. TC002: Verificar el envío de datos de un paciente que cumple todos los criterios de elegibilidad. → El sistema muestra el estado 'Elegible' de forma clara e inmediata y un desglose que indica que todos los criterios de inclusión se cumplieron y ningún criterio de exclusión bloqueó la elegibilidad.
3. TC003: Verificar que el formulario valida los campos obligatorios antes del envío. → El sistema muestra un mensaje claro indicando que los campos obligatorios deben ser completados y no permite el envío del formulario.
4. TC004: Verificar la validación de rango para el campo 'Edad' (menor a 18). → El sistema muestra un mensaje de validación claro indicando que la 'edad debe ser mayor o igual a 18' y no permite el envío del formulario.
5. TC005: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por embarazo. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'embarazo' bloqueó la elegibilidad.
6. TC006: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por PASI < 10. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'PASI >= 10' no se cumplió.
7. TC007: Verificar que el motor evalúa correctamente los casos límite para los criterios de inclusión. → El sistema muestra el estado 'Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que todos los criterios de inclusión, incluyendo los valores límite, se cumplieron.
8. TC008: Verificar que todos los selectores del formulario utilizan el componente CustomSelect interno. → Todos los componentes de selección de opciones están implementados utilizando el estándar interno CustomSelect.
9. TC009: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por historial de TB. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'historial de TB' bloqueó la elegibilidad.
10. TC010: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por uso reciente de biológicos. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'biológicos en los últimos 90 días' bloqueó la elegibilidad.
11. TC011: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por uso reciente de corticoides tópicos. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'corticoides en los últimos 14 días' bloqueó la elegibilidad.
12. TC012: Verificar la validación de rango para el campo 'PASI' (valor no válido). → El sistema muestra un mensaje de validación claro indicando que el 'PASI debe estar en un rango válido' (e.g., entre 0 y 100) y no permite el envío del formulario.
13. TC013: Verificar que se muestra un estado de carga durante el proceso de evaluación. → La interfaz de usuario muestra un indicador visual de carga (e.g., spinner, mensaje 'Evaluando...') mientras el sistema procesa la solicitud.
14. TC014: Verificar el manejo de errores si el motor de pre-screening falla durante la comunicación. → El sistema muestra un mensaje de error claro al usuario (e.g., 'Error al evaluar la elegibilidad. Intente de nuevo más tarde.') y no muestra un resultado de elegibilidad.
15. TC015: Verificar que el gestor del ensayo puede auditar las decisiones del sistema. → El sistema permite al gestor del ensayo ver el historial de evaluaciones de pacientes, incluyendo la decisión final 'Elegible'/'No Elegible' y el desglose de todos los criterios evaluados, tanto cumplidos como bloqueados.
16. TC016: Verificar la validación de rango para el campo 'BSA' (valor no válido). → El sistema muestra un mensaje de validación claro indicando que el 'BSA debe estar en un rango válido' y no permite el envío del formulario.
17. TC017: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por diagnóstico de Psoriasis < 6 meses. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'diagnóstico crónico >= 6 meses' no se cumplió.
18. TC018: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por BSA < 10%. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que el criterio 'BSA >= 10%' no se cumplió.
19. TC019: Verificar que el motor clasifica correctamente a un paciente como 'No Elegible' por múltiples criterios de exclusión. → El sistema muestra el estado 'No Elegible' de forma clara e inmediata y el desglose de trazabilidad indica que los criterios 'historial de TB' y 'embarazo' bloquearon la elegibilidad.
20. TC020: Verificar que el panel de auditoría muestra estadísticas de reclutamiento. → El sistema muestra estadísticas de reclutamiento, incluyendo el número de pacientes 'aprobados', 'rechazados' y los 'motivos' principales de rechazo.
21. TC021: Verificar que un Investigador Clínico puede iniciar sesión exitosamente. → El sistema autentica al usuario y redirige al Investigador Clínico al formulario de evaluación de pacientes.
22. TC022: Verificar que el generador de datos sintéticos puede crear un perfil 'Golden Path' elegible. → El sistema genera un perfil de paciente ficticio que cumple con todos los criterios de inclusión y ningún criterio de exclusión, resultando en un paciente 'Elegible'.
23. NFR001: Verificar tiempo de respuesta del motor de elegibilidad. → El resultado de elegibilidad ("Elegible" o "No Elegible") y su trazabilidad se muestra de forma instantánea e inmediata (ej. < 2 segundos).
24. NFR002: Evaluar el rendimiento del sistema bajo carga de usuarios concurrentes. → El sistema debe escalar automáticamente mediante ECS/Fargate, manteniendo una latencia de respuesta aceptable (ej. < 5 segundos) y sin errores de servicio.
25. NFR003: Verificar que las credenciales no se exponen en archivos .env en producción. → No se encuentran archivos `.env` ni credenciales sensibles hardcodeadas en el entorno de producción. Todas las credenciales se cargan desde AWS Secrets Manager.
26. NFR004: Validar la autenticación y autorización de usuarios mediante JWT y Laravel Passport. → 1. Acceso denegado con código de estado 401 (Unauthorized). 2. Acceso permitido a la ruta protegida. 3. Acceso denegado con código de estado 401 (Unauthorized).
27. NFR005: Verificar la alta disponibilidad de la base de datos MySQL en un escenario de falla. → La base de datos realiza una conmutación por error automática y exitosa a la instancia secundaria en el tiempo esperado (ej. < 60 segundos), y el servicio del backend se recupera sin pérdida de datos ni interrupción.
28. NFR006: Validar que los errores del sistema se registran correctamente en AWS CloudWatch. → El error provocado se registra en AWS CloudWatch con el nivel de severidad correcto y detalles estructurados que permiten su análisis y trazabilidad.
29. NFR007: Evaluar la efectividad de Amazon ElastiCache Redis para el almacenamiento en caché. → La segunda solicitud tiene un tiempo de respuesta significativamente menor que la primera, indicando que la respuesta fue servida desde ElastiCache Redis. La carga en la base de datos MySQL para la segunda solicitud es mínima.
30. NFR008: Verificar que solo se utilizan datos sintéticos y no PHI real. → El sistema solo procesa y almacena los 15 perfiles de pacientes ficticios, sin ninguna exposición de PHI real, cumpliendo con la "validación estricta del dataset para evitar violaciones de privacidad".
31. NFR009: Comprobar el comportamiento del sistema ante la indisponibilidad de AWS Secrets Manager. → El backend debe registrar un error claro sobre la imposibilidad de obtener credenciales de Secrets Manager y no debe exponer información sensible. El servicio debe fallar de forma controlada o no iniciar.
32. NFR010: Comprobar el comportamiento del sistema ante la indisponibilidad de AWS AppConfig. → El backend debe registrar un error claro sobre la imposibilidad de obtener parámetros de AppConfig y no debe exponer información sensible. El servicio debe fallar de forma controlada o no iniciar.
33. NFR011: Validar el rendimiento y accesibilidad del componente CustomSelect. → El CustomSelect funciona de manera fluida, es completamente accesible mediante teclado y no utiliza la etiqueta `<select>` nativa de HTML ni librerías externas, cumpliendo con el estándar interno.
34. NFR012: Verificar el uso exclusivo de UUID v4/v7 para claves primarias. → Todas las claves primarias de las entidades creadas son UUID v4 o v7, y no se utilizan IDs autoincrementales, según lo especificado en la arquitectura.
35. NFR013: Verificar el cifrado de datos en tránsito entre frontend y backend. → Toda la comunicación entre el frontend (S3/CloudFront) y el backend (ECS/Fargate) se realiza exclusivamente a través de HTTPS/TLS, asegurando el cifrado de los datos en tránsito.
36. NFR014: Validar el escalado automático de instancias de backend en ECS/Fargate. → El servicio ECS/Fargate escala automáticamente, añadiendo nuevas tareas cuando los umbrales de CPU o peticiones son excedidos, y reduciendo las tareas cuando la carga disminuye, manteniendo la disponibilidad y el rendimiento.
37. NFR015: Verificar coherencia de contratos DTOs entre Backend y Frontend. → Las interfaces TypeScript del frontend son exactamente idénticas a los DTOs del backend, garantizando la coherencia de los contratos.
38. NFR016: Validar que la cobertura de pruebas del backend cumple con el mínimo requerido. → La cobertura de pruebas en las capas de Dominio y Aplicación del backend es al menos del 80%, según lo especificado.
39. NFR017: Comprobar que la arquitectura del backend previene la filtración de infraestructura al dominio. → El test `arch()` de Pest PHP se ejecuta exitosamente, confirmando que la infraestructura no se filtra al dominio.
40. NFR018: Verificar la capacidad de recuperar la base de datos MySQL a partir de snapshots automáticos. → La base de datos RDS MySQL se recupera exitosamente a partir de un snapshot automático, sin pérdida de datos crítica y permitiendo la operación normal de la aplicación.

## TEAM SCOPE (MANDATORY — PARSED BY THE PIPELINE)
**Role:** role-tl (technical_lead)
**Role:** role-be (backend_developer)
**Role:** role-fe (frontend_developer)
**Role:** role-devops (devops_support)

## 3. EXECUTABLE ITEMS

### ITEM 1: Foundation — Backend Core y Contratos Compartidos
**Goal:** Configurar el proyecto Laravel, definir las migraciones de base de datos, los seeders iniciales, los DTOs del backend, los enums del dominio, los clientes para servicios AWS (Secrets Manager, AppConfig, CloudWatch), la infraestructura de autenticación (Cognito JWT Decoder, Passport Cognito Guard) y los DTOs/tokens del frontend. Esto establece todos los contratos compartidos y la infraestructura fundamental.
**Files to create:**
- backend/composer.json (create)
- backend/artisan (create)
- backend/bootstrap/app.php (create)
- backend/bootstrap/providers.php (create)
- backend/config/app.php (create)
- backend/config/auth.php (create)
- backend/config/database.php (create)
- backend/config/cache.php (create)
- backend/config/session.php (create)
- backend/config/logging.php (create)
- backend/config/cors.php (create)
- backend/config/passport.php (create)
- backend/config/secrets.php (create)
- backend/config/appconfig.php (create)
- backend/database/migrations/2026_01_01_000001_create_paciente_table.php (create)
- backend/database/migrations/2026_01_01_000002_create_usuario_table.php (create)
- backend/database/migrations/2026_01_01_000003_create_evaluacion_table.php (create)
- backend/database/migrations/2026_01_01_000004_create_resultado_evaluacion_table.php (create)
- backend/database/migrations/2026_01_01_000005_create_regla_criterio_table.php (create)
- backend/database/migrations/2026_01_01_000006_create_evaluacion_criterio_table.php (create)
- backend/database/migrations/2026_01_01_000007_create_auditoria_table.php (create)
- backend/database/seeders/DatabaseSeeder.php (create)
- backend/database/seeders/ReglaCriterioSeeder.php (create)
- backend/database/seeders/PerfilSinteticoSeeder.php (create)
- backend/app/Domain/Entities/Enums/Rol.php (create)
- backend/app/Domain/Entities/Enums/Resultado.php (create)
- backend/app/Domain/Entities/Enums/EstadoCriterio.php (create)
- backend/app/Domain/Entities/Enums/TipoCriterio.php (create)
- backend/app/Application/DTOs/PacienteDTO.php (create)
- backend/app/Application/DTOs/EvaluacionDTO.php (create)
- backend/app/Application/DTOs/ResultadoEvaluacionDTO.php (create)
- backend/app/Application/DTOs/ReglaCriterioDTO.php (create)
- backend/app/Application/DTOs/EvaluacionCriterioDTO.php (create)
- backend/app/Application/DTOs/UsuarioDTO.php (create)
- backend/app/Application/DTOs/AuditoriaDTO.php (create)
- backend/app/Application/DTOs/CrearEvaluacionRequest.php (create)
- backend/app/Application/DTOs/EvaluacionResolutionResponse.php (create)
- backend/app/Application/DTOs/EstadisticasReclutamientoDTO.php (create)
- backend/app/Infrastructure/Auth/CognitoJWTDecoder.php (create)
- backend/app/Infrastructure/Auth/CognitoUserProvider.php (create)
- backend/app/Infrastructure/Auth/PassportCognitoGuard.php (create)
- backend/app/Infrastructure/AWS/SecretsManagerClient.php (create)
- backend/app/Infrastructure/AWS/AppConfigClient.php (create)
- backend/app/Infrastructure/AWS/CloudWatchLogger.php (create)
- frontend/src/types/dto.ts (create)
- frontend/src/styles/tokens.ts (create)
**Dependencies:** None
**Validation:** Ejecutar `php artisan migrate` y `php artisan db:seed` en el contenedor del backend para verificar que las tablas se crean y los datos iniciales se siembran correctamente.
**Role:** role-tl (technical_lead)

### ITEM 2: Backend — Motor de Elegibilidad y Creación de Evaluación
**Goal:** Implementar el motor de elegibilidad clínico central, el endpoint `POST /api/v1/evaluaciones` y los servicios de dominio y casos de uso asociados para la creación de pacientes, evaluaciones, resultados y criterios, así como el registro de auditoría.
**Files to create:**
- backend/app/Domain/Entities/Paciente.php (create)
- backend/app/Domain/Entities/Evaluacion.php (create)
- backend/app/Domain/Entities/ResultadoEvaluacion.php (create)
- backend/app/Domain/Entities/ReglaCriterio.php (create)
- backend/app/Domain/Entities/EvaluacionCriterio.php (create)
- backend/app/Domain/Entities/Auditoria.php (create)
- backend/app/Domain/Services/MotorElegibilidad.php (create)
- backend/app/Domain/Services/EvaluadorCriterio.php (create)
- backend/app/Domain/Services/ResolvedorResultado.php (create)
- backend/app/Domain/Repositories/PacienteRepositoryInterface.php (create)
- backend/app/Domain/Repositories/EvaluacionRepositoryInterface.php (create)
- backend/app/Domain/Repositories/ResultadoEvaluacionRepositoryInterface.php (create)
- backend/app/Domain/Repositories/ReglaCriterioRepositoryInterface.php (create)
- backend/app/Domain/Repositories/EvaluacionCriterioRepositoryInterface.php (create)
- backend/app/Domain/Repositories/AuditoriaRepositoryInterface.php (create)
- backend/app/Domain/Exceptions/PacienteNoEncontrado.php (create)
- backend/app/Domain/Exceptions/EvaluacionInvalida.php (create)
- backend/app/Application/UseCases/EvaluarElegibilidad.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Models/Paciente.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Models/Evaluacion.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Models/ResultadoEvaluacion.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Models/ReglaCriterio.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Models/EvaluacionCriterio.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Models/Auditoria.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Repositories/EloquentPacienteRepository.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Repositories/EloquentEvaluacionRepository.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Repositories/EloquentResultadoEvaluacionRepository.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Repositories/EloquentReglaCriterioRepository.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Repositories/EloquentEvaluacionCriterioRepository.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Repositories/EloquentAuditoriaRepository.php (create)
- backend/app/Infrastructure/Http/Controllers/EvaluacionController.php (create)
- backend/app/Infrastructure/Http/Requests/CrearEvaluacionRequest.php (create)
- backend/routes/api.php (create)
**Dependencies:** Item 1
**Validation:** Enviar una solicitud POST válida a `/api/v1/evaluaciones` y verificar que la respuesta `201` contenga el `evaluacion_id`, `resultado` y `detalle_trazabilidad` esperados, y que los datos se persistan en la base de datos.
**Role:** role-be (backend_developer)

### ITEM 3: Backend — Consulta y Listado de Evaluaciones y Criterios
**Goal:** Implementar los endpoints `GET /api/v1/evaluaciones` (listado paginado), `GET /api/v1/evaluaciones/{evaluacionId}` (detalle completo), `GET /api/v1/evaluaciones/{evaluacionId}/export` (exportación JSON) y `GET /api/v1/criterios` (listado de reglas de criterios activos).
**Files to create:**
- backend/app/Application/UseCases/ConsultarEvaluacion.php (create)
- backend/app/Application/UseCases/ListarEvaluaciones.php (create)
- backend/app/Application/UseCases/ListarCriterios.php (create)
- backend/app/Infrastructure/Http/Controllers/CriterioController.php (create)
- (Modificar) backend/app/Infrastructure/Http/Controllers/EvaluacionController.php (añadir métodos para GET y exportación)
- (Modificar) backend/routes/api.php (añadir rutas para los nuevos endpoints)
**Dependencies:** Item 1, Item 2
**Validation:**
1.  Realizar una solicitud GET a `/api/v1/evaluaciones` y verificar que devuelve una lista paginada de evaluaciones.
2.  Realizar una solicitud GET a `/api/v1/evaluaciones/{evaluacionId}` con un ID existente y verificar que devuelve el detalle completo de la evaluación.
3.  Realizar una solicitud GET a `/api/v1/evaluaciones/{evaluacionId}/export` y verificar que descarga un archivo JSON con el detalle de la evaluación.
4.  Realizar una solicitud GET a `/api/v1/criterios` y verificar que devuelve la lista de reglas de criterios activos.
**Role:** role-be (backend_developer)

### ITEM 4: Backend — Auditoría y Estadísticas
**Goal:** Implementar los endpoints `GET /api/v1/auditoria` (listado paginado de registros de auditoría), `GET /api/v1/estadisticas/reclutamiento` (métricas de reclutamiento), `GET /api/v1/auditoria/export.csv` (exportación CSV de auditoría) y el endpoint de salud `GET /api/v1/health`. También se implementará el middleware de roles.
**Files to create:**
- backend/app/Domain/Entities/Usuario.php (create)
- backend/app/Domain/Repositories/UsuarioRepositoryInterface.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Models/Usuario.php (create)
- backend/app/Infrastructure/Persistence/Eloquent/Repositories/EloquentUsuarioRepository.php (create)
- backend/app/Application/UseCases/ListarAuditoria.php (create)
- backend/app/Application/UseCases/ConsultarEstadisticas.php (create)
- backend/app/Application/UseCases/ExportarAuditoriaCsv.php (create)
- backend/app/Infrastructure/Http/Controllers/AuditoriaController.php (create)
- backend/app/Infrastructure/Http/Controllers/EstadisticasController.php (create)
- backend/app/Infrastructure/Http/Controllers/HealthController.php (create)
- backend/app/Infrastructure/Http/Middleware/EnsureRol.php (create)
- (Modificar) backend/bootstrap/app.php (registrar el middleware `EnsureRol`)
- (Modificar) backend/routes/api.php (añadir rutas para los nuevos endpoints y aplicar middleware de roles)
**Dependencies:** Item 1, Item 2
**Validation:**
1.  Realizar una solicitud GET a `/api/v1/health` y verificar que devuelve `{"status": "ok", ...}`.
2.  Realizar una solicitud GET a `/api/v1/auditoria` y verificar que devuelve una lista paginada de registros de auditoría.
3.  Realizar una solicitud GET a `/api/v1/estadisticas/reclutamiento` y verificar que devuelve las métricas de reclutamiento.
4.  Realizar una solicitud GET a `/api/v1/auditoria/export.csv` y verificar que descarga un archivo CSV con los registros de auditoría.
5.  Verificar que los endpoints protegidos por rol (`EnsureRol`) deniegan el acceso a usuarios sin el rol adecuado (403 Forbidden).
**Role:** role-be (backend_developer)

### ITEM 5: Frontend — Autenticación y Estructura de la Aplicación
**Goal:** Implementar el flujo de autenticación de usuarios con AWS Amplify/Cognito, la estructura principal de la aplicación (layout) y el enrutamiento. Esto incluye el punto de entrada de React, el cliente API con manejo de JWT y los componentes de navegación.
**Files to create:**
- frontend/package.json (create)
- frontend/tsconfig.json (create)
- frontend/vite.config.ts (create)
- frontend/index.html (create)
- frontend/src/main.tsx (create)
- frontend/src/App.tsx (create)
- frontend/src/api/client.ts (create)
- frontend/src/api/auth.ts (create)
- frontend/src/hooks/useAuth.ts (create)
- frontend/src/components/ui/NavegacionPrincipal.tsx (create)
- frontend/src/components/layout/PlantillaClinica.tsx (create)
**Dependencies:** Item 1
**Validation:**
1.  Acceder a la aplicación en el navegador y verificar que se muestra la página de inicio de sesión.
2.  Iniciar sesión con credenciales de Cognito y verificar que el usuario es redirigido a la página principal y la navegación muestra el nombre/rol del usuario.
3.  Cerrar sesión y verificar que el usuario vuelve a la página de inicio de sesión.
**Role:** role-fe (frontend_developer)

### ITEM 6: Frontend — Formulario de Evaluación de Pacientes
**Goal:** Implementar la página "Formulario de Evaluación" con todos los campos de entrada para datos del paciente y de la evaluación, incluyendo validación de formulario, manejo de estado y lógica de envío a la API. Se utilizarán los componentes UI/UX base.
**Files to create:**
- frontend/src/pages/FormularioEvaluacion.tsx (create)
- frontend/src/api/evaluaciones.ts (create)
- frontend/src/hooks/useEvaluacion.ts (create)
- frontend/src/components/ui/BotonAccionPrincipal.tsx (create)
- frontend/src/components/ui/TarjetaClinica.tsx (create)
- frontend/src/components/ui/CampoFormulario.tsx (create)
- frontend/src/components/ui/CustomSelect.tsx (create)
- frontend/src/utils/validation.ts (create)
- frontend/src/utils/formatters.ts (create)
**Dependencies:** Item 1, Item 5
**Validation:**
1.  Navegar a la página del formulario de evaluación.
2.  Intentar enviar el formulario con campos obligatorios vacíos y verificar que se muestran los mensajes de validación (TC003).
3.  Introducir una edad menor a 18 y verificar el mensaje de validación (TC004).
4.  Introducir un PASI o BSA fuera de rango y verificar los mensajes de validación (TC012, TC016).
5.  Completar el formulario con datos válidos y enviarlo, verificando que se muestra un estado de carga (TC013) y luego se redirige al panel de resolución.
6.  Verificar que todos los selectores usan el componente `CustomSelect` (TC008).
**Role:** role-fe (frontend_developer)

### ITEM 7: Frontend — Panel de Resolución de Elegibilidad
**Goal:** Implementar la página "Panel de Resolución de Elegibilidad" para mostrar los resultados de la evaluación de un paciente, incluyendo el estado de elegibilidad, los datos del paciente, el desglose de criterios de inclusión/exclusión y las acciones de trazabilidad (exportar JSON).
**Files to create:**
- frontend/src/pages/PanelResolucionElegibilidad.tsx (create)
- frontend/src/components/ui/InsigniaEstado.tsx (create)
- frontend/src/components/ui/ListaCriterios.tsx (create)
**Dependencies:** Item 1, Item 5, Item 6
**Validation:**
1.  Después de enviar un formulario de evaluación (desde Item 6), verificar que la página de resolución muestra el estado 'Elegible' o 'No Elegible' y el desglose de criterios (TC002, TC005, TC006, TC007, TC009, TC010, TC011, TC017, TC018, TC019).
2.  Hacer clic en el botón de exportar y verificar que se descarga el JSON de la evaluación.
3.  Simular un error en la comunicación con el backend y verificar que se muestra un mensaje de error claro (TC014).
**Role:** role-fe (frontend_developer)

### ITEM 8: Frontend — Panel de Auditoría y Estadísticas
**Goal:** Implementar la página "Panel de Auditoría y Estadísticas de Reclutamiento" con filtros de búsqueda, métricas clave, distribución de motivos de exclusión y una tabla de auditoría paginada. Se incluirá la funcionalidad de exportación a CSV.
**Files to create:**
- frontend/src/pages/PanelAuditoriaEstadisticas.tsx (create)
- frontend/src/api/auditoria.ts (create)
- frontend/src/api/estadisticas.ts (create)
- frontend/src/hooks/useAuditoria.ts (create)
- frontend/src/hooks/useEstadisticas.ts (create)
- frontend/src/components/ui/MetricaEstadistica.tsx (create)
- frontend/src/components/ui/TablaAuditoria.tsx (create)
**Dependencies:** Item 1, Item 5
**Validation:**
1.  Navegar a la página de auditoría y estadísticas.
2.  Verificar que se muestran las estadísticas de reclutamiento (total, elegibles, no elegibles, motivos de exclusión) (TC020).
3.  Verificar que la tabla de auditoría muestra los registros y permite la paginación.
4.  Aplicar filtros (fecha, estado, usuario) y verificar que los resultados se actualizan.
5.  Hacer clic en el botón de exportar CSV y verificar que se descarga el archivo.
6.  Verificar que el gestor del ensayo puede auditar las decisiones del sistema (TC015).
**Role:** role-fe (frontend_developer)

### ITEM 9: Dockerización del Backend
**Goal:** Crear el Dockerfile para el servicio de backend de Laravel, configurando un entorno de ejecución PHP-FPM con Nginx.
**Files to create:**
- backend/Dockerfile (create)
**Dependencies:** Item 1, Item 2, Item 3, Item 4
**Validation:** Construir la imagen Docker del backend (`docker build -f backend/Dockerfile -t motor-clinico-backend .`) y verificar que la imagen se crea correctamente y que el servidor Nginx/PHP-FPM está configurado para escuchar en el puerto 23080.
**Role:** role-devops (devops_support)

### ITEM 10: Dockerización del Frontend
**Goal:** Crear el Dockerfile para la aplicación frontend de React, utilizando un build multi-stage para optimizar la imagen final y servir la aplicación estática con Nginx.
**Files to create:**
- frontend/Dockerfile (create)
**Dependencies:** Item 1, Item 5, Item 6, Item 7, Item 8
**Validation:** Construir la imagen Docker del frontend (`docker build -f frontend/Dockerfile --build-arg VITE_API_BASE_URL=http://localhost:23080/api/v1 -t motor-clinico-frontend .`) y verificar que la imagen se crea correctamente y que Nginx está configurado para servir los archivos estáticos en el puerto 23081.
**Role:** role-devops (devops_support)

### ITEM 11: Infraestructura y Despliegue Local
**Goal:** Configurar `docker-compose.yml` para orquestar todos los servicios (backend, frontend, MySQL, Redis), crear el script `run.sh` para automatizar el inicio del entorno local, y definir los archivos `.env.example`, `README.md`, `.gitignore` y `.dockerignore`.
**Files to create:**
- docker-compose.yml (create)
- .env.example (create)
- .gitignore (create)
- .dockerignore (create)
- run.sh (create)
- README.md (create)
**Dependencies:** Item 1, Item 2, Item 3, Item 4, Item 5, Item 6, Item 7, Item 8, Item 9, Item 10
**Validation:** Ejecutar `./run.sh` en la raíz del proyecto y verificar que:
1.  Docker está corriendo.
2.  Todas las imágenes se construyen y los contenedores se inician sin errores.
3.  Los servicios de MySQL y Redis están saludables.
4.  El backend y el frontend están saludables y accesibles.
5.  La aplicación web es accesible en `http://localhost:23081` y todas las funcionalidades (autenticación, evaluación, resolución, auditoría, estadísticas) operan correctamente.
**Role:** role-devops (devops_support)