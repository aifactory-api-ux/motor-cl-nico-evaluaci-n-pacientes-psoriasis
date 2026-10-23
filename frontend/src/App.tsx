import {
    useCallback,
    useEffect,
    useMemo,
    useState,
    type CSSProperties,
    type FormEvent,
    type ReactNode,
} from 'react';
import { BrowserRouter, Navigate, Outlet, Route, Routes, useNavigate } from 'react-router-dom';

import { ApiError, apiGet } from './api/client';
import {
    cerrarSesion as cerrarSesionCognito,
    confirmarSesion,
    hayConfiguracionCognito,
    iniciarSesion,
    mensajeErrorAutenticacion,
    obtenerSesion,
} from './api/auth';
import { tokens } from './styles/tokens';
import type { ReglaCriterioDTO, UsuarioDTO } from './types/dto';

/**
 * Estructura principal de la aplicación del protocolo ALM-PSO-2026.
 *
 * Este módulo reúne el enrutado, el control de sesión y las pantallas
 * disponibles en la entrega actual: el inicio de sesión contra Amazon Cognito
 * y el resumen operativo del protocolo, que consume la API del backend con el
 * token Bearer. Las páginas clínicas (formulario de evaluación, resolución y
 * auditoría) se incorporan como rutas adicionales sobre este mismo esqueleto.
 */

/** Estilo base de la página. */
const estilosPagina: CSSProperties = {
    minHeight: '100vh',
    margin: 0,
    backgroundColor: tokens.colors.neutral.background,
    color: tokens.colors.neutral.text_primary,
    fontFamily: tokens.typography.font_family,
    fontSize: tokens.typography.scale.md.size,
    lineHeight: tokens.typography.scale.md.line_height,
};

/** Contenedor central de las pantallas. */
const estilosContenedor: CSSProperties = {
    width: '100%',
    maxWidth: '960px',
    margin: '0 auto',
    padding: `${tokens.spacing[8]} ${tokens.spacing[6]}`,
};

/** Tarjeta clínica de superficie blanca. */
const estilosTarjeta: CSSProperties = {
    backgroundColor: tokens.colors.neutral.surface,
    border: `1px solid ${tokens.colors.neutral.border}`,
    borderRadius: tokens.radii.lg,
    boxShadow: tokens.shadows.sm,
    padding: tokens.spacing[6],
};

/** Etiqueta de campo de formulario. */
const estilosEtiqueta: CSSProperties = {
    display: 'block',
    marginBottom: tokens.spacing[2],
    color: tokens.colors.neutral.text_secondary,
    fontSize: tokens.typography.scale.sm.size,
    lineHeight: tokens.typography.scale.sm.line_height,
    fontWeight: tokens.typography.font_weights.semibold,
    letterSpacing: tokens.typography.scale.sm.letter_spacing,
};

/** Campo de entrada del formulario de acceso. */
const estilosEntrada: CSSProperties = {
    width: '100%',
    padding: `${tokens.spacing[3]} ${tokens.spacing[4]}`,
    border: `1px solid ${tokens.colors.neutral.border_strong}`,
    borderRadius: tokens.radii.md,
    backgroundColor: tokens.colors.neutral.white,
    color: tokens.colors.neutral.text_primary,
    fontFamily: tokens.typography.font_family,
    fontSize: tokens.typography.scale.lg.size,
    lineHeight: tokens.typography.scale.lg.line_height,
};

/** Botón de acción principal del protocolo. */
const estilosBotonPrimario: CSSProperties = {
    width: '100%',
    padding: `${tokens.spacing[3]} ${tokens.spacing[4]}`,
    border: 'none',
    borderRadius: tokens.radii.md,
    backgroundColor: tokens.colors.primary.main,
    color: tokens.colors.neutral.white,
    fontFamily: tokens.typography.font_family,
    fontSize: tokens.typography.scale.lg.size,
    lineHeight: tokens.typography.scale.lg.line_height,
    fontWeight: tokens.typography.font_weights.semibold,
    cursor: 'pointer',
    transition: `background-color ${tokens.motion.duration_fast} ${tokens.motion.easing}`,
};

/** Botón secundario para acciones de apoyo. */
const estilosBotonSecundario: CSSProperties = {
    padding: `${tokens.spacing[2]} ${tokens.spacing[4]}`,
    border: `1px solid ${tokens.colors.neutral.border_strong}`,
    borderRadius: tokens.radii.md,
    backgroundColor: tokens.colors.neutral.surface,
    color: tokens.colors.primary.main,
    fontFamily: tokens.typography.font_family,
    fontSize: tokens.typography.scale.sm.size,
    lineHeight: tokens.typography.scale.sm.line_height,
    fontWeight: tokens.typography.font_weights.semibold,
    cursor: 'pointer',
    transition: `background-color ${tokens.motion.duration_fast} ${tokens.motion.easing}`,
};

/** Mensaje de error accesible para lectores de pantalla. */
const estilosError: CSSProperties = {
    margin: 0,
    padding: `${tokens.spacing[3]} ${tokens.spacing[4]}`,
    border: `1px solid ${tokens.colors.semantic.error}`,
    borderRadius: tokens.radii.md,
    backgroundColor: tokens.colors.semantic.error_surface,
    color: tokens.colors.semantic.error,
    fontSize: tokens.typography.scale.sm.size,
    lineHeight: tokens.typography.scale.sm.line_height,
};

/** Mensaje informativo de entorno. */
const estilosAviso: CSSProperties = {
    margin: 0,
    padding: `${tokens.spacing[3]} ${tokens.spacing[4]}`,
    border: `1px solid ${tokens.colors.primary.focus}`,
    borderRadius: tokens.radii.md,
    backgroundColor: tokens.colors.semantic.info_surface,
    color: tokens.colors.semantic.info,
    fontSize: tokens.typography.scale.sm.size,
    lineHeight: tokens.typography.scale.sm.line_height,
};

/**
 * Traduce el rol técnico del protocolo a su denominación para el usuario.
 */
function etiquetaRol(rol: UsuarioDTO['rol']): string {
    return rol === 'gestor' ? 'Gestor del Ensayo' : 'Investigador Clínico';
}

/**
 * Pantalla de inicio de sesión del protocolo.
 */
export function PantallaInicioSesion({
    onSesionIniciada,
}: {
    onSesionIniciada: (usuario: UsuarioDTO) => void;
}) {
    const [correo, setCorreo] = useState<string>('');
    const [password, setPassword] = useState<string>('');
    const [codigo, setCodigo] = useState<string>('');
    const [requiereConfirmacion, setRequiereConfirmacion] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);
    const [enviando, setEnviando] = useState<boolean>(false);

    const configurado = hayConfiguracionCognito();

    const manejarEnvio = async (evento: FormEvent<HTMLFormElement>): Promise<void> => {
        evento.preventDefault();
        setError(null);

        if (correo.trim() === '' || password.trim() === '') {
            setError('El correo y la contraseña son obligatorios.');
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.trim())) {
            setError('El correo institucional no tiene un formato válido.');
            return;
        }

        if (requiereConfirmacion && codigo.trim() === '') {
            setError('Introduzca el código de confirmación recibido para continuar.');
            return;
        }

        setEnviando(true);

        try {
            const resultado = requiereConfirmacion
                ? await confirmarSesion(codigo)
                : await iniciarSesion(correo, password);

            if (resultado.autenticado && resultado.usuario !== null) {
                onSesionIniciada(resultado.usuario);
                return;
            }

            setRequiereConfirmacion(true);
            setError(
                'Amazon Cognito solicita un código de confirmación para completar el acceso al protocolo.',
            );
        } catch (fallo) {
            setError(mensajeErrorAutenticacion(fallo));
        } finally {
            setEnviando(false);
        }
    };

    return (
        <main style={{ ...estilosPagina, display: 'flex', alignItems: 'center' }}>
            <div style={{ ...estilosContenedor, maxWidth: '520px' }}>
                <header style={{ marginBottom: tokens.spacing[6] }}>
                    <p
                        style={{
                            margin: 0,
                            color: tokens.colors.primary.main,
                            fontSize: tokens.typography.scale.sm.size,
                            lineHeight: tokens.typography.scale.sm.line_height,
                            fontWeight: tokens.typography.font_weights.bold,
                            letterSpacing: tokens.typography.scale.sm.letter_spacing,
                        }}
                    >
                        Protocolo ALM-PSO-2026
                    </p>
                    <h1
                        style={{
                            margin: `${tokens.spacing[2]} 0 ${tokens.spacing[3]}`,
                            fontSize: tokens.typography.scale['2xl'].size,
                            lineHeight: tokens.typography.scale['2xl'].line_height,
                            letterSpacing: tokens.typography.scale['2xl'].letter_spacing,
                            fontWeight: tokens.typography.font_weights.bold,
                        }}
                    >
                        Motor de Pre-selección Clínica Etapa PO
                    </h1>
                    <p
                        style={{
                            margin: 0,
                            color: tokens.colors.neutral.text_secondary,
                            fontSize: tokens.typography.scale.lg.size,
                            lineHeight: tokens.typography.scale.lg.line_height,
                        }}
                    >
                        Evaluación de elegibilidad para el ensayo clínico de psoriasis en placas.
                    </p>
                </header>

                <section style={estilosTarjeta} aria-labelledby="titulo-acceso">
                    <h2
                        id="titulo-acceso"
                        style={{
                            margin: `0 0 ${tokens.spacing[5]}`,
                            fontSize: tokens.typography.scale.xl.size,
                            lineHeight: tokens.typography.scale.xl.line_height,
                            fontWeight: tokens.typography.font_weights.semibold,
                        }}
                    >
                        Inicio de sesión
                    </h2>

                    {!configurado && (
                        <p style={{ ...estilosAviso, marginBottom: tokens.spacing[5] }} role="status">
                            El entorno no tiene configurado el user pool de Amazon Cognito. Defina
                            VITE_COGNITO_USER_POOL_ID y VITE_COGNITO_CLIENT_ID para habilitar el
                            acceso de investigadores y gestores.
                        </p>
                    )}

                    <form onSubmit={(evento) => void manejarEnvio(evento)} noValidate>
                        <div style={{ marginBottom: tokens.spacing[5] }}>
                            <label htmlFor="correo" style={estilosEtiqueta}>
                                Correo institucional
                            </label>
                            <input
                                id="correo"
                                name="correo"
                                type="email"
                                autoComplete="username"
                                value={correo}
                                onChange={(evento) => setCorreo(evento.target.value)}
                                style={estilosEntrada}
                                aria-required="true"
                                aria-invalid={error !== null ? 'true' : 'false'}
                                aria-describedby={error !== null ? 'error-acceso' : undefined}
                                disabled={enviando || !configurado}
                            />
                        </div>

                        <div style={{ marginBottom: tokens.spacing[5] }}>
                            <label htmlFor="password" style={estilosEtiqueta}>
                                Contraseña
                            </label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="current-password"
                                value={password}
                                onChange={(evento) => setPassword(evento.target.value)}
                                style={estilosEntrada}
                                aria-required="true"
                                disabled={enviando || !configurado}
                            />
                        </div>

                        {requiereConfirmacion && (
                            <div style={{ marginBottom: tokens.spacing[5] }}>
                                <label htmlFor="codigo" style={estilosEtiqueta}>
                                    Código de confirmación
                                </label>
                                <input
                                    id="codigo"
                                    name="codigo"
                                    type="text"
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    value={codigo}
                                    onChange={(evento) => setCodigo(evento.target.value)}
                                    style={estilosEntrada}
                                    aria-required="true"
                                    disabled={enviando}
                                />
                            </div>
                        )}

                        {error !== null && (
                            <p id="error-acceso" role="alert" style={{ ...estilosError, marginBottom: tokens.spacing[5] }}>
                                {error}
                            </p>
                        )}

                        <button
                            type="submit"
                            style={{
                                ...estilosBotonPrimario,
                                backgroundColor: enviando
                                    ? tokens.colors.primary.pressed
                                    : tokens.colors.primary.main,
                                cursor: enviando || !configurado ? 'progress' : 'pointer',
                            }}
                            disabled={enviando || !configurado}
                            aria-busy={enviando}
                        >
                            {enviando ? 'Verificando credenciales…' : 'Acceder al motor'}
                        </button>
                    </form>

                    <p
                        style={{
                            margin: `${tokens.spacing[5]} 0 0`,
                            color: tokens.colors.neutral.text_muted,
                            fontSize: tokens.typography.scale.xs.size,
                            lineHeight: tokens.typography.scale.xs.line_height,
                        }}
                    >
                        Entorno clínico seguro · Los datos procesados son sintéticos y no contienen
                        información de pacientes reales.
                    </p>
                </section>

                <aside
                    style={{
                        marginTop: tokens.spacing[6],
                        color: tokens.colors.neutral.text_secondary,
                        fontSize: tokens.typography.scale.sm.size,
                        lineHeight: tokens.typography.scale.sm.line_height,
                    }}
                >
                    <h3
                        style={{
                            margin: `0 0 ${tokens.spacing[2]}`,
                            color: tokens.colors.neutral.text_primary,
                            fontSize: tokens.typography.scale.md.size,
                            lineHeight: tokens.typography.scale.md.line_height,
                            fontWeight: tokens.typography.font_weights.semibold,
                        }}
                    >
                        Ayuda de acceso y soporte
                    </h3>
                    <p style={{ margin: 0 }}>
                        El acceso está reservado al Investigador Clínico y al Gestor del Ensayo del
                        protocolo ALM-PSO-2026. Si no recuerda sus credenciales, solicite el
                        restablecimiento al gestor del ensayo.
                    </p>
                </aside>
            </div>
        </main>
    );
}

/**
 * Guarda de acceso: redirige al inicio de sesión cuando no hay sesión activa.
 */
export function RutaProtegida({
    usuario,
    cargando,
}: {
    usuario: UsuarioDTO | null;
    cargando: boolean;
}) {
    if (cargando) {
        return (
            <main style={estilosPagina}>
                <div style={{ ...estilosContenedor, textAlign: 'center' }}>
                    <p
                        role="status"
                        aria-live="polite"
                        style={{
                            margin: 0,
                            color: tokens.colors.neutral.text_secondary,
                            fontSize: tokens.typography.scale.lg.size,
                            lineHeight: tokens.typography.scale.lg.line_height,
                        }}
                    >
                        Verificando la sesión activa…
                    </p>
                </div>
            </main>
        );
    }

    if (usuario === null) {
        return <Navigate to="/login" replace />;
    }

    return <Outlet />;
}

/**
 * Resumen operativo del protocolo para el usuario autenticado.
 *
 * Muestra el estado del motor, el usuario en sesión y los criterios clínicos
 * que el backend expone para el protocolo, consumiendo la API con el token
 * Bearer de Amazon Cognito.
 */
export function PanelProtocolo({
    usuario,
    onCerrarSesion,
}: {
    usuario: UsuarioDTO | null;
    onCerrarSesion: () => void;
}) {
    const [estado, setEstado] = useState<'cargando' | 'listo' | 'error'>('cargando');
    const [mensajeEstado, setMensajeEstado] = useState<string | null>(null);
    const [criterios, setCriterios] = useState<ReglaCriterioDTO[]>([]);
    const [errorCriterios, setErrorCriterios] = useState<string | null>(null);

    const cargarProtocolo = useCallback(async (): Promise<void> => {
        setEstado('cargando');
        setErrorCriterios(null);

        try {
            const [salud, lista] = await Promise.all([
                apiGet<{ status: string }>('/api/v1/health'),
                apiGet<ReglaCriterioDTO[]>('/api/v1/criterios'),
            ]);

            setMensajeEstado(salud?.status === 'ok' ? 'Operativo' : 'Estado no identificado');
            setCriterios(Array.isArray(lista) ? lista : []);
            setEstado('listo');
        } catch (error) {
            setEstado('error');
            setErrorCriterios(
                error instanceof ApiError
                    ? error.message
                    : 'No fue posible consultar los criterios del protocolo. Intente de nuevo más tarde.',
            );
        }
    }, []);

    useEffect(() => {
        void cargarProtocolo();
    }, [cargarProtocolo]);

    const inclusiones = useMemo(
        () => criterios.filter((criterio) => criterio.tipo === 'inclusion'),
        [criterios],
    );

    const exclusiones = useMemo(
        () => criterios.filter((criterio) => criterio.tipo === 'exclusion'),
        [criterios],
    );

    return (
        <div style={estilosPagina}>
            <header
                style={{
                    backgroundColor: tokens.colors.neutral.surface,
                    borderBottom: `1px solid ${tokens.colors.neutral.border}`,
                    boxShadow: tokens.shadows.sm,
                }}
            >
                <div
                    style={{
                        ...estilosContenedor,
                        maxWidth: '1200px',
                        display: 'flex',
                        flexWrap: 'wrap',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        gap: tokens.spacing[4],
                        padding: `${tokens.spacing[4]} ${tokens.spacing[6]}`,
                    }}
                >
                    <div>
                        <p
                            style={{
                                margin: 0,
                                color: tokens.colors.primary.main,
                                fontSize: tokens.typography.scale.xs.size,
                                lineHeight: tokens.typography.scale.xs.line_height,
                                fontWeight: tokens.typography.font_weights.bold,
                                letterSpacing: tokens.typography.scale.xs.letter_spacing,
                            }}
                        >
                            ALM-PSO-2026 · Motor de Pre-selección Clínica Etapa PO
                        </p>
                        <h1
                            style={{
                                margin: `${tokens.spacing[1]} 0 0`,
                                fontSize: tokens.typography.scale.xl.size,
                                lineHeight: tokens.typography.scale.xl.line_height,
                                fontWeight: tokens.typography.font_weights.semibold,
                            }}
                        >
                            Resumen del protocolo
                        </h1>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', gap: tokens.spacing[4] }}>
                        {usuario !== null && (
                            <div style={{ textAlign: 'right' }}>
                                <p
                                    style={{
                                        margin: 0,
                                        color: tokens.colors.neutral.text_primary,
                                        fontSize: tokens.typography.scale.md.size,
                                        lineHeight: tokens.typography.scale.md.line_height,
                                        fontWeight: tokens.typography.font_weights.semibold,
                                    }}
                                >
                                    {usuario.nombre}
                                </p>
                                <p
                                    style={{
                                        margin: 0,
                                        color: tokens.colors.neutral.text_muted,
                                        fontSize: tokens.typography.scale.xs.size,
                                        lineHeight: tokens.typography.scale.xs.line_height,
                                    }}
                                >
                                    {etiquetaRol(usuario.rol)}
                                </p>
                            </div>
                        )}

                        <button
                            type="button"
                            onClick={onCerrarSesion}
                            style={estilosBotonSecundario}
                        >
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </header>

            <main style={estilosContenedor}>
                <section style={{ ...estilosTarjeta, marginBottom: tokens.spacing[6] }}>
                    <h2
                        style={{
                            margin: `0 0 ${tokens.spacing[4]}`,
                            fontSize: tokens.typography.scale.lg.size,
                            lineHeight: tokens.typography.scale.lg.line_height,
                            fontWeight: tokens.typography.font_weights.semibold,
                        }}
                    >
                        Estado del motor de elegibilidad
                    </h2>

                    {estado === 'cargando' && (
                        <p
                            role="status"
                            aria-live="polite"
                            style={{
                                margin: 0,
                                color: tokens.colors.neutral.text_secondary,
                                fontSize: tokens.typography.scale.md.size,
                                lineHeight: tokens.typography.scale.md.line_height,
                            }}
                        >
                            Consultando el estado del motor…
                        </p>
                    )}

                    {estado === 'error' && (
                        <>
                            <p role="alert" style={{ ...estilosError, marginBottom: tokens.spacing[4] }}>
                                {errorCriterios}
                            </p>
                            <button
                                type="button"
                                onClick={() => void cargarProtocolo()}
                                style={estilosBotonSecundario}
                            >
                                Reintentar
                            </button>
                        </>
                    )}

                    {estado === 'listo' && (
                        <p
                            style={{
                                margin: 0,
                                color: tokens.colors.semantic.success,
                                fontSize: tokens.typography.scale.md.size,
                                lineHeight: tokens.typography.scale.md.line_height,
                                fontWeight: tokens.typography.font_weights.medium,
                            }}
                        >
                            Motor de elegibilidad: {mensajeEstado}
                        </p>
                    )}
                </section>

                <section style={estilosTarjeta}>
                    <h2
                        style={{
                            margin: `0 0 ${tokens.spacing[2]}`,
                            fontSize: tokens.typography.scale.lg.size,
                            lineHeight: tokens.typography.scale.lg.line_height,
                            fontWeight: tokens.typography.font_weights.semibold,
                        }}
                    >
                        Criterios clínicos del protocolo
                    </h2>
                    <p
                        style={{
                            margin: `0 0 ${tokens.spacing[5]}`,
                            color: tokens.colors.neutral.text_secondary,
                            fontSize: tokens.typography.scale.sm.size,
                            lineHeight: tokens.typography.scale.sm.line_height,
                        }}
                    >
                        Reglas que el motor aplica a cada candidato para determinar su elegibilidad.
                    </p>

                    {estado === 'listo' && (
                        <div style={{ display: 'grid', gap: tokens.spacing[6] }}>
                            <ListaCriterios titulo="Criterios de inclusión" criterios={inclusiones} />
                            <ListaCriterios titulo="Criterios de exclusión" criterios={exclusiones} />
                        </div>
                    )}
                </section>
            </main>
        </div>
    );
}

/**
 * Lista de criterios clínicos con su código, nombre y descripción.
 */
function ListaCriterios({
    titulo,
    criterios,
}: {
    titulo: string;
    criterios: ReglaCriterioDTO[];
}) {
    return (
        <div>
            <h3
                style={{
                    margin: `0 0 ${tokens.spacing[3]}`,
                    color: tokens.colors.neutral.text_secondary,
                    fontSize: tokens.typography.scale.md.size,
                    lineHeight: tokens.typography.scale.md.line_height,
                    fontWeight: tokens.typography.font_weights.bold,
                    textTransform: 'uppercase',
                    letterSpacing: tokens.typography.scale.xs.letter_spacing,
                }}
            >
                {titulo}
            </h3>

            {criterios.length === 0 ? (
                <p
                    style={{
                        margin: 0,
                        color: tokens.colors.neutral.text_muted,
                        fontSize: tokens.typography.scale.sm.size,
                        lineHeight: tokens.typography.scale.sm.line_height,
                    }}
                >
                    No hay criterios activos registrados para este grupo.
                </p>
            ) : (
                <ul style={{ listStyle: 'none', margin: 0, padding: 0, display: 'grid', gap: tokens.spacing[3] }}>
                    {criterios.map((criterio) => (
                        <li
                            key={criterio.id}
                            style={{
                                display: 'flex',
                                flexWrap: 'wrap',
                                alignItems: 'baseline',
                                gap: tokens.spacing[3],
                                padding: `${tokens.spacing[3]} ${tokens.spacing[4]}`,
                                borderRadius: tokens.radii.md,
                                backgroundColor: tokens.colors.neutral.surface_muted,
                            }}
                        >
                            <span
                                style={{
                                    color: tokens.colors.primary.main,
                                    fontSize: tokens.typography.scale.xs.size,
                                    lineHeight: tokens.typography.scale.xs.line_height,
                                    fontWeight: tokens.typography.font_weights.bold,
                                    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace',
                                }}
                            >
                                {criterio.codigo}
                            </span>
                            <span
                                style={{
                                    color: tokens.colors.neutral.text_primary,
                                    fontSize: tokens.typography.scale.md.size,
                                    lineHeight: tokens.typography.scale.md.line_height,
                                    fontWeight: tokens.typography.font_weights.medium,
                                }}
                            >
                                {criterio.nombre}
                            </span>
                            <span
                                style={{
                                    flexBasis: '100%',
                                    color: tokens.colors.neutral.text_secondary,
                                    fontSize: tokens.typography.scale.sm.size,
                                    lineHeight: tokens.typography.scale.sm.line_height,
                                }}
                            >
                                {criterio.descripcion}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

/**
 * Enrutado de la aplicación y control de la sesión del usuario.
 */
function RutasClinicas(): ReactNode {
    const [usuario, setUsuario] = useState<UsuarioDTO | null>(null);
    const [cargando, setCargando] = useState<boolean>(true);
    const navegar = useNavigate();

    const recuperarSesion = useCallback(async (): Promise<void> => {
        setCargando(true);

        try {
            const sesion = await obtenerSesion();
            setUsuario(sesion?.usuario ?? null);
        } catch {
            setUsuario(null);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        void recuperarSesion();
    }, [recuperarSesion]);

    const manejarInicioSesion = useCallback(
        (usuarioAutenticado: UsuarioDTO): void => {
            setUsuario(usuarioAutenticado);
            navegar('/', { replace: true });
        },
        [navegar],
    );

    const manejarCierreSesion = useCallback((): void => {
        void cerrarSesionCognito().finally(() => {
            setUsuario(null);
            navegar('/login', { replace: true });
        });
    }, [navegar]);

    return (
        <Routes>
            <Route
                path="/login"
                element={<PantallaInicioSesion onSesionIniciada={manejarInicioSesion} />}
            />

            <Route element={<RutaProtegida usuario={usuario} cargando={cargando} />}>
                <Route
                    path="/"
                    element={<PanelProtocolo usuario={usuario} onCerrarSesion={manejarCierreSesion} />}
                />
            </Route>

            <Route
                path="*"
                element={<Navigate to={usuario !== null ? '/' : '/login'} replace />}
            />
        </Routes>
    );
}

/**
 * Componente raíz de la aplicación.
 */
export default function App() {
    return (
        <BrowserRouter>
            <RutasClinicas />
        </BrowserRouter>
    );
}
