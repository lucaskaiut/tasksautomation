import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = Number(env.VITE_DEV_SERVER_PORT || 5173);
    const publicPort = env.VITE_DEV_SERVER_PUBLIC_PORT
        ? Number(env.VITE_DEV_SERVER_PUBLIC_PORT)
        : undefined;
    const hmrHost = env.VITE_HMR_HOST || 'localhost';

    return {
        server: {
            host: true,
            port,
            strictPort: true,
            hmr: {
                host: hmrHost,
                ...(publicPort !== undefined && publicPort !== port
                    ? { clientPort: publicPort }
                    : {}),
            },
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
    };
});
