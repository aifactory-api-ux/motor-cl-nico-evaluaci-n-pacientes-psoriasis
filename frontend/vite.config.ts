import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Configuración de Vite para el frontend del protocolo ALM-PSO-2026.
 *
 * El frontend se sirve como SPA estática: nginx (contenedor o S3 + CloudFront)
 * entrega los assets y redirige las rutas /api/* al backend Laravel. Durante el
 * desarrollo local el servidor de Vite hace de proxy hacia el backend, por lo
 * que el navegador siempre trabaja con rutas relativas y nunca con URLs
 * absolutas incrustadas en el bundle.
 */
export default defineConfig(({ mode }) => {
    const entorno = loadEnv(mode, '.', '');

    const puerto = Number.parseInt(entorno.VITE_PORT ?? '', 10);

    return {
        plugins: [react()],
        server: {
            host: true,
            port: Number.isNaN(puerto) ? 23081 : puerto,
            strictPort: false,
            proxy: {
                '/api': {
                    target: 'http://127.0.0.1:23080',
                    changeOrigin: true,
                },
            },
        },
        preview: {
            host: true,
            port: Number.isNaN(puerto) ? 23081 : puerto,
        },
        build: {
            outDir: 'dist',
            sourcemap: false,
        },
    };
});
