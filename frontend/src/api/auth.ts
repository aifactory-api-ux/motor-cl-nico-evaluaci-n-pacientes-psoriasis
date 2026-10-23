/**
 * Capa de autenticación del frontend del protocolo ALM-PSO-2026.
 *
 * La identidad se gestiona con Amazon Cognito mediante el módulo `Auth` de
 * AWS Amplify. El backend Laravel valida el token Bearer con Laravel Passport
 * (`auth:api`), de modo que el frontend nunca expone credenciales del pool y
 * solo transmite el token de sesión.
 *
 * La configuración del pool llega por variables de entorno de Vite
 * (`VITE_COGNITO_USER_POOL_ID`, `VITE_COGNITO_CLIENT_ID`); la región del pool se
 * deduce de su identificador. Ninguna URL de backend se incrusta aquí: la API se
 * consume siempre con rutas relativas a través del cliente HTTP.
 */
import { Amplify } from 'aws-amplify';
import { confirmSignIn, fetchAuthSession, signIn, signOut } from 'aws-amplify/auth';

import type { UsuarioDTO } from '../types/dto';
import { establecerProveedorToken, guardarToken } from './client';

/** Claims de Cognito que identifican al usuario por su correo. */
export const CLAIMS_CORREO = ['email', 'cognito:username', 'preferred_username'] as const;

/** Claim de atributo personalizado que transporta el rol del protocolo. */
export const CLAIM_ROL = 'custom:rol';

/** Claim de Cognito con los grupos a los que pertenece el usuario. */
export const CLAIM_GRUPOS = 'cognito:groups';

/** Roles admitidos por el motor de pre-selección. */
export const ROLES = ['investigador', 'gestor'] as const;

/** Rol utilizado cuando el token no transporta uno explícito. */
export const ROL_POR_DEFECTO = 'investigador';

/**
 * Datos de conexión al user pool de Amazon Cognito.
 *
 * La región no se declara de forma explícita: el módulo `Auth` de Amplify la
 * deduce del identificador del pool (`<region>_<identificador>`).
 */
export interface ConfiguracionCognito {
    userPoolId: string;
    userPoolClientId: string;
}

/**
 * Sesión activa del usuario del protocolo.
 */
export interface SesionCognito {
    /** Usuario del protocolo con su rol asignado. */
    usuario: UsuarioDTO;
    /** Token Bearer que valida Laravel Passport. */
    accessToken: string;
    /** Token de identidad de Cognito. */
    idToken: string;
}

/**
 * Resultado de un intento de inicio de sesión.
 */
export interface ResultadoInicioSesion {
    /** Indica si la sesión quedó abierta. */
    autenticado: boolean;
    /** Indica que Cognito exige una segunda confirmación (por ejemplo un código SMS). */
    requiereConfirmacion: boolean;
    /** Paso de confirmación solicitado por Cognito, si lo hay. */
    paso: string | null;
    /** Usuario del protocolo cuando la autenticación se completó. */
    usuario: UsuarioDTO | null;
}

/** Variable de entorno leída desde el bundle de Vite. */
function entorno(nombre: string): string | null {
    const valor = (import.meta.env as unknown as Record<string, string | undefined>)[nombre];

    return typeof valor === 'string' && valor.trim() !== '' ? valor.trim() : null;
}

/**
 * Devuelve la configuración del user pool declarada en el entorno.
 */
export function configuracionCognito(): ConfiguracionCognito {
    return {
        userPoolId: entorno('VITE_COGNITO_USER_POOL_ID') ?? '',
        userPoolClientId: entorno('VITE_COGNITO_CLIENT_ID') ?? '',
    };
}

/**
 * Indica si el entorno dispone de los datos necesarios para autenticar.
 */
export function hayConfiguracionCognito(): boolean {
    const configuracion = configuracionCognito();

    return configuracion.userPoolId !== '' && configuracion.userPoolClientId !== '';
}

/**
 * Configura Amplify con el user pool del protocolo.
 *
 * @returns `true` cuando el pool quedó configurado.
 */
export function configurarAmplify(): boolean {
    if (!hayConfiguracionCognito()) {
        return false;
    }

    const { userPoolId, userPoolClientId } = configuracionCognito();

    Amplify.configure({
        Auth: {
            Cognito: {
                userPoolId,
                userPoolClientId,
                loginWith: {
                    email: true,
                },
            },
        },
    });

    // El cliente HTTP resuelve el token vigente en cada petición, de modo que
    // la renovación que hace Cognito se propaga sin intervencion manual.
    establecerProveedorToken(async () => {
        const sesion = await fetchAuthSession();

        return sesion.tokens?.accessToken?.toString() ?? null;
    });

    return true;
}

/**
 * Decodifica el payload de un JWT de Cognito.
 *
 * @param token Token JWT (`id` o `access`) emitido por el user pool.
 */
export function decodificarClaims(token: string): Record<string, unknown> {
    const partes = token.split('.');

    if (partes.length !== 3) {
        return {};
    }

    const base64 = partes[1].replace(/-/g, '+').replace(/_/g, '/');
    const relleno = base64.length % 4 === 0 ? '' : '='.repeat(4 - (base64.length % 4));

    try {
        const binario = window.atob(base64 + relleno);
        const json = decodeURIComponent(
            binario
                .split('')
                .map((caracter) => `%${caracter.charCodeAt(0).toString(16).padStart(2, '0')}`)
                .join(''),
        );

        const datos: unknown = JSON.parse(json);

        return datos !== null && typeof datos === 'object' ? (datos as Record<string, unknown>) : {};
    } catch {
        return {};
    }
}

/**
 * Extrae el correo del usuario de los claims del token.
 */
function correoDeClaims(claims: Record<string, unknown>): string {
    for (const clave of CLAIMS_CORREO) {
        const valor = claims[clave];

        if (typeof valor === 'string' && valor.trim() !== '') {
            return valor.trim();
        }
    }

    return '';
}

/**
 * Determina el rol del protocolo a partir de los claims del token.
 */
function rolDeClaims(claims: Record<string, unknown>): UsuarioDTO['rol'] {
    const rol = claims[CLAIM_ROL];

    if (rol === 'gestor' || rol === 'investigador') {
        return rol;
    }

    const grupos = claims[CLAIM_GRUPOS];

    if (Array.isArray(grupos) && grupos.includes('gestor')) {
        return 'gestor';
    }

    return ROL_POR_DEFECTO;
}

/**
 * Construye el usuario del protocolo a partir de los claims del token.
 */
export function construirUsuario(claims: Record<string, unknown>): UsuarioDTO {
    const correo = correoDeClaims(claims);
    const nombre = claims['name'] ?? claims['given_name'];
    const sub = claims['sub'];

    return {
        id: typeof sub === 'string' && sub !== '' ? sub : correo,
        email: correo,
        nombre:
            typeof nombre === 'string' && nombre.trim() !== ''
                ? nombre.trim()
                : correo !== ''
                  ? correo.split('@')[0]
                  : 'Usuario del protocolo',
        rol: rolDeClaims(claims),
    };
}

/**
 * Traduce un error de Cognito a un mensaje comprensible para el usuario clínico.
 */
export function mensajeErrorAutenticacion(error: unknown): string {
    const nombre = (error as { name?: unknown } | null)?.name;
    const mensajeOriginal = (error as { message?: unknown } | null)?.message;

    const detalle = typeof mensajeOriginal === 'string' ? mensajeOriginal : '';

    switch (nombre) {
        case 'UserNotFoundException':
            return 'No existe una cuenta registrada con ese correo en el protocolo.';
        case 'NotAuthorizedException':
            return 'Las credenciales no son correctas. Verifique el correo y la contraseña.';
        case 'UserNotConfirmedException':
            return 'La cuenta todavía no ha sido confirmada por el administrador del protocolo.';
        case 'PasswordResetRequiredException':
            return 'Debe restablecer la contraseña antes de acceder al motor.';
        case 'TooManyRequestsException':
            return 'Demasiados intentos de acceso. Espere unos minutos e intente de nuevo.';
        case 'InvalidPasswordException':
            return 'La contraseña no cumple las reglas de seguridad del protocolo.';
        case 'NetworkError':
            return 'No fue posible contactar con Amazon Cognito. Intente de nuevo más tarde.';
        default:
            return detalle.trim() !== ''
                ? `No fue posible iniciar sesión: ${detalle.trim()}`
                : 'No fue posible iniciar sesión. Intente de nuevo más tarde.';
    }
}

/**
 * Inicia sesión en el user pool del protocolo.
 *
 * @param correo Correo institucional del usuario.
 * @param password Contraseña del usuario.
 */
export async function iniciarSesion(correo: string, password: string): Promise<ResultadoInicioSesion> {
    if (!hayConfiguracionCognito()) {
        throw new Error(
            'El entorno no tiene configurado el user pool de Amazon Cognito (VITE_COGNITO_USER_POOL_ID y VITE_COGNITO_CLIENT_ID).',
        );
    }

    const resultado = await signIn({ username: correo.trim(), password });

    if (resultado.isSignedIn) {
        const sesion = await obtenerSesion();

        if (sesion === null) {
            throw new Error('La sesión de Amazon Cognito se cerró de forma inesperada.');
        }

        guardarToken(sesion.accessToken);

        return {
            autenticado: true,
            requiereConfirmacion: false,
            paso: null,
            usuario: sesion.usuario,
        };
    }

    const paso = resultado.nextStep.signInStep;

    return {
        autenticado: false,
        requiereConfirmacion: true,
        paso,
        usuario: null,
    };
}

/**
 * Completa el segundo paso del inicio de sesión (código de confirmación).
 *
 * @param codigo Código recibido por el canal configurado en el pool.
 */
export async function confirmarSesion(codigo: string): Promise<ResultadoInicioSesion> {
    await confirmSignIn({ challengeResponse: codigo.trim() });

    const sesion = await obtenerSesion();

    if (sesion === null) {
        throw new Error('La sesión de Amazon Cognito se cerró de forma inesperada.');
    }

    guardarToken(sesion.accessToken);

    return {
        autenticado: true,
        requiereConfirmacion: false,
        paso: null,
        usuario: sesion.usuario,
    };
}

/**
 * Cierra la sesión del usuario tanto en Cognito como en el cliente HTTP.
 */
export async function cerrarSesion(): Promise<void> {
    try {
        await signOut();
    } finally {
        guardarToken(null);
    }
}

/**
 * Devuelve la sesión activa, o `null` cuando no hay usuario autenticado.
 */
export async function obtenerSesion(): Promise<SesionCognito | null> {
    let tokens;

    try {
        const sesion = await fetchAuthSession();
        tokens = sesion.tokens;
    } catch {
        return null;
    }

    const accessToken = tokens?.accessToken?.toString() ?? '';
    const idToken = tokens?.idToken?.toString() ?? '';

    if (accessToken === '') {
        return null;
    }

    const claims = decodificarClaims(idToken !== '' ? idToken : accessToken);

    return {
        usuario: construirUsuario(claims),
        accessToken,
        idToken,
    };
}
