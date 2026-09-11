import { defineConfig, externalizeDepsPlugin } from 'electron-vite';
import { join } from 'path';

export default defineConfig({
    main: {
        build: {
            rollupOptions: {
                plugins: [
                    {
                        name: 'watch-external',
                        buildStart() {
                            const appPath = process.env.APP_PATH || join(process.cwd(), '..', '..');
                            this.addWatchFile(
                                join(appPath, 'app', 'Providers', 'NativeAppServiceProvider.php'),
                            );
                        },
                    },
                ],
            },
        },
        plugins: [externalizeDepsPlugin()],
    },
});