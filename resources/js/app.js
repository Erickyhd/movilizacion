import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

createInertiaApp({
    title: (title) => title ? `${title} - Movilización` : 'Sistema de Traslado de Personal',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.use(ZiggyVue);

        if (import.meta.env.DEV) {
            app.config.devtools = true;
            app.config.performance = true;
        }

        return app.mount(el);
    },
    progress: {
        color: '#3b82f6',
    },
});