import './bootstrap';

import { createApp, createSSRApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { Icon } from '@iconify/vue';
import { Head, Link } from '@inertiajs/vue3';
import 'boxicons';
import { resolvePage } from './resolvePage';

createInertiaApp({
    title: (title) => `${title}`,
    resolve: resolvePage,

    setup({ el, App, props, plugin }) {
        // Hydrate server-rendered markup when SSR is on; plain mount otherwise.
        const create = el.hasChildNodes() ? createSSRApp : createApp;

        return create({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Icon', Icon)
            .component('Head', Head)
            .component('Link', Link)
            .mount(el);
    },

    progress: {
        color: '#1f9db5',
    },
});
