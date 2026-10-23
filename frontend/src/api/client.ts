/**
 * Cliente HTTP del frontend del protocolo ALM-PSO-2026.
 *
 * Reglas de operación (contrato de despliegue):
 *
 * - Todas las peticiones usan rutas RELATIVAS. El navegador nunca ve nombres de
 *   host de Docker ni URLs absolutas: nginx (contenedor o S3 + CloudFront)
 *   enruta `/api/*` hacia el backend Laravel, que publica sus rutas bajo
 *   `/api/v1`.
 * - Este módulo NO antepone ningún prefijo. Quien llama indica la ruta completa
 *   (por ejemplo `/api/v1/evaluaciones`). De este modo no puede producirse la
 *   duplicación `/api/api/recurso`.
 * - La cabecera `Authorization: Bearer <token>` se añade de forma automática con
 *   el token emitido por Amazon Cognito, que valida Laravel Passport.
 */

/**
 * Cuerpo de error devuelto por la API del backend.
 */
export interface ApiErrorPayload {
    message?: string;
    errors?: Record<string, string[]>;
}

/**
 * Error normalizado de la capa de comunicación con el backend.
 *
 * El estado 0 identifica fallos de conexión (no se pudo alcanzar el servicio)
 * y los estados 401/403/5xx proceden de respuestas del backend.
 */
export class ApiError extends Error {
    /** Estado HTTP de la respuesta; 0 cuando la petición no llegó al servidor. */
    readonly status: number;

    /** Contenido JSON del error, cuando el backend lo devuelve. */
    readonly payload: ApiErrorPayload | null;

    constructor(message: string, status: number, payload: ApiErrorPayload | null = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.payload = payload;
    }

    /** Indica que el backend rechazó la credencial del usuario. */
    get esNoAutorizado(): boolean {
        return this.status === 401;
    }

    /** Indica que el usuario no tiene permiso para la operación solicitada. */
    get esProhibido(): boolean {
        return this.status === 403;
    }

    /** Indica un fallo del servicio del backend. */
    get esDeServidor(): boolean {
        return this.status >= 500;
    }

    /** Indica que la comunicación con el backend falló. */
    get esDeConexion(): boolean {
        return this.status === 0;
    }
}

/**
 * Proveedor del token Bearer vigente. Puede ser síncrono o asíncrono para
 * permitir la renovación del token de Cognito antes de cada petición.
 */
export type ProveedorToken = () => string | null | Promise<string | null>;

/**
 * Opciones admitidas por {@link apiFetch}.
 */
export interface ApiRequestOptions extends Omit<RequestInit, 'body'> {
    /** Cuerpo de la petición; se serializa como JSON cuando es distinto de undefined. */
    body?: unknown;
    /** Token explícito; si se omite se resuelve el token vigente de Cognito. */
    token?: string | null;
    /** Formato esperado de la respuesta. */
    accept?: 'json' | 'text' | 'blob';
    /** Permite omitir la cabecera Authorization en endpoints públicos. */
    sinAutenticacion?: boolean;
}

/** Clave bajo la que se conserva el token Bearer durante la sesión del navegador. */
const CLAVE_TOKEN_SESION = 'alm-pso-2026.token';

/** Token vigente en memoria, evitando leer el almacenamiento en cada petición. */
let tokenEnMemoria: string | null = null;

/** Proveedor registrado por la capa de autenticación (Amazon Cognito). */
let proveedorToken: ProveedorToken | null = null;

/**
 * Registra el proveedor que resuelve el token Bearer vigente.
 *
 * @param proveedor Función que devuelve el token o `null` si no hay sesión.
 */
export function establecerProveedorToken(proveedor: ProveedorToken | null): void {
    proveedorToken = proveedor;
}

/**
 * Conserva el token Bearer de la sesión activa.
 *
 * @param token Token emitido por Cognito o `null` para cerrar la sesión.
 */
export function guardarToken(token: string | null): void {
    tokenEnMemoria = token;

    try {
        if (token === null) {
            window.sessionStorage.removeItem(CLAVE_TOKEN_SESION);
            return;
        }

        window.sessionStorage.setItem(CLAVE_TOKEN_SESION, token);
    } catch {
        // El almacenamiento del navegador puede estar bloqueado: el token en
        // memoria es suficiente mientras la pestaña permanezca abierta.
    }
}

/**
 * Devuelve el token conservado en memoria o en el almacenamiento de la sesión.
 */
export function obtenerTokenGuardado(): string | null {
    if (tokenEnMemoria !== null) {
        return tokenEnMemoria;
    }

    try {
        const almacenado = window.sessionStorage.getItem(CLAVE_TOKEN_SESION);
        tokenEnMemoria = almacenado !== null && almacenado !== '' ? almacenado : null;
    } catch {
        tokenEnMemoria = null;
    }

    return tokenEnMemoria;
}

/**
 * Resuelve el token Bearer que se enviará en la cabecera Authorization.
 *
 * El proveedor registrado (Cognito) tiene prioridad porque renueva el token
 * cuando Amazon Cognito lo refresca; si el proveedor falla, se recurre al
 * token conservado en la sesión.
 */
export async function resolverToken(): Promise<string | null> {
    if (proveedorToken !== null) {
        try {
            const token = await proveedorToken();

            if (typeof token === 'string' && token !== '') {
                guardarToken(token);
                return token;
            }
        } catch {
            // Un fallo del proveedor no debe impedir el uso del token en caché.
        }
    }

    return obtenerTokenGuardado();
}

/**
 * Traduce el cuerpo de una respuesta de error en un mensaje para el usuario.
 */
function mensajeDeError(status: number, payload: ApiErrorPayload | null): string {
    if (payload !== null) {
        if (typeof payload.message === 'string' && payload.message.trim() !== '') {
            return payload.message.trim();
        }

        if (payload.errors !== undefined) {
            const mensajes = Object.values(payload.errors)
                .flat()
                .filter((mensaje) => typeof mensaje === 'string' && mensaje.trim() !== '');

            if (mensajes.length > 0) {
                return mensajes.join(' ');
            }
        }
    }

    if (status === 401) {
        return 'No autenticado. Se requiere un token Bearer válido.';
    }

    if (status === 403) {
        return 'No autorizado para ejecutar esta operación.';
    }

    if (status === 0) {
        return 'No fue posible conectar con el motor de elegibilidad. Intente de nuevo más tarde.';
    }

    return `La operación no pudo completarse (estado ${status}). Intente de nuevo más tarde.`;
}

/**
 * Construye las cabeceras de la petición con el token Bearer cuando corresponde.
 */
async function construirCabeceras(
    opciones: ApiRequestOptions,
): Promise<Headers> {
    const cabeceras = new Headers();

    if (opciones.headers !== undefined) {
        new Headers(opciones.headers).forEach((valor, clave) => cabeceras.set(clave, valor));
    }

    const accept = opciones.accept ?? 'json';

    cabeceras.set('Accept', accept === 'json' ? 'application/json' : accept);

    if (opciones.body !== undefined && !(opciones.body instanceof FormData)) {
        cabeceras.set('Content-Type', 'application/json');
    }

    if (!opciones.sinAutenticacion) {
        const token = opciones.token !== undefined ? opciones.token : await resolverToken();

        if (typeof token === 'string' && token !== '') {
            cabeceras.set('Authorization', `Bearer ${token}`);
        }
    }

    return cabeceras;
}

/**
 * Ejecuta una petición contra la API del backend y devuelve el cuerpo tipado.
 *
 * @param ruta Ruta relativa completa, por ejemplo `/api/v1/evaluaciones`.
 * @param opciones Opciones de la petición (cuerpo, token, formato, cancelación).
 */
export async function apiFetch<T = unknown>(
    ruta: string,
    opciones: ApiRequestOptions = {},
): Promise<T> {
    const cabeceras = await construirCabeceras(opciones);

    const inicial: RequestInit = {
        method: opciones.method ?? 'GET',
        headers: cabeceras,
        signal: opciones.signal,
    };

    if (opciones.body !== undefined && opciones.body !== null) {
        inicial.body =
            opciones.body instanceof FormData
                ? opciones.body
                : JSON.stringify(opciones.body);
    }

    let respuesta: Response;

    try {
        respuesta = await fetch(ruta, inicial);
    } catch (error) {
        throw new ApiError(
            'No fue posible conectar con el motor de elegibilidad. Intente de nuevo más tarde.',
            0,
            null,
        );
    }

    if (!respuesta.ok) {
        let cuerpo: ApiErrorPayload | null = null;

        try {
            const texto = await respuesta.text();

            if (texto.trim() !== '') {
                const analizado: unknown = JSON.parse(texto);

                cuerpo =
                    analizado !== null && typeof analizado === 'object'
                        ? (analizado as ApiErrorPayload)
                        : null;
            }
        } catch {
            cuerpo = null;
        }

        if (respuesta.status === 401) {
            guardarToken(null);
        }

        throw new ApiError(mensajeDeError(respuesta.status, cuerpo), respuesta.status, cuerpo);
    }

    if (respuesta.status === 204) {
        return undefined as T;
    }

    const accept = opciones.accept ?? 'json';

    if (accept === 'blob') {
        return (await respuesta.blob()) as T;
    }

    if (accept === 'text') {
        return (await respuesta.text()) as T;
    }

    const texto = await respuesta.text();

    if (texto.trim() === '') {
        return undefined as T;
    }

    return JSON.parse(texto) as T;
}

/**
 * Ejecuta una petición GET y devuelve el cuerpo tipado.
 */
export function apiGet<T = unknown>(
    ruta: string,
    opciones: ApiRequestOptions = {},
): Promise<T> {
    return apiFetch<T>(ruta, { ...opciones, method: 'GET' });
}

/**
 * Ejecuta una petición POST con cuerpo JSON y devuelve la respuesta tipada.
 */
export function apiPost<T = unknown>(
    ruta: string,
    cuerpo?: unknown,
    opciones: ApiRequestOptions = {},
): Promise<T> {
    return apiFetch<T>(ruta, { ...opciones, method: 'POST', body: cuerpo });
}

/**
 * Descarga un recurso binario (exportaciones) como Blob.
 */
export function apiBlob(ruta: string, opciones: ApiRequestOptions = {}): Promise<Blob> {
    return apiFetch<Blob>(ruta, { ...opciones, method: 'GET', accept: 'blob' });
}

/**
 * Descarga un recurso de texto (por ejemplo una exportación CSV).
 */
export function apiTexto(ruta: string, opciones: ApiRequestOptions = {}): Promise<string> {
    return apiFetch<string>(ruta, { ...opciones, method: 'GET', accept: 'text' });
}
