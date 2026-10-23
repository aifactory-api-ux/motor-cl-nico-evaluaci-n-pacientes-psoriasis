import React from 'react';
import ReactDOM from 'react-dom/client';

import App from './App';
import { configurarAmplify } from './api/auth';

/**
 * Componente raíz que monta la aplicación del protocolo ALM-PSO-2026.
 */
export default function Aplicacion(): React.ReactElement {
    return (
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
}

/**
 * Punto de entrada de la SPA del protocolo ALM-PSO-2026.
 *
 * Antes de montar la aplicación se configura el módulo de autenticación de
 * AWS Amplify con el user pool de Amazon Cognito, de modo que la sesión del
 * usuario quede disponible para el cliente HTTP.
 */
configurarAmplify();

const contenedor = document.getElementById('root');

if (contenedor === null) {
    throw new Error('No se encontró el contenedor raíz "root" en index.html.');
}

ReactDOM.createRoot(contenedor).render(<Aplicacion />);
