import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';

const pages = import.meta.glob('./pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `${title} · ByteStreak` : 'ByteStreak — one byte a day'),
    resolve: (name) => pages[`./pages/${name}.vue`](),
    // Signed-in pages share one persistent shell; the landing page brings its own.
    layout: (name) => {
        if (name === 'Landing') return null;
        return name.startsWith('auth/') ? GuestLayout : AppLayout;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: { color: '#ff6b2c', showSpinner: false },
});
