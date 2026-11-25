// Inertia SSR entry. Built by `vite build --ssr` into bootstrap/ssr/ssr.js and
// served by `php artisan inertia:start-ssr` (a long-running Node process).
// Enable on the Laravel side with INERTIA_SSR_ENABLED=true.
//
// Anything touching window/document/localStorage must stay inside onMounted
// (or be guarded) — this file runs in Node, where those don't exist.
import { createInertiaApp, Head, Link } from "@inertiajs/vue3";
import createServer from "@inertiajs/vue3/server";
import { renderToString } from "vue/server-renderer";
import { createSSRApp, h } from "vue";
import { ZiggyVue, route as ziggyRoute } from "../../vendor/tightenco/ziggy";
import { Icon } from "@iconify/vue";
import { resolvePage } from "./resolvePage";

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title}`,
        resolve: resolvePage,
        setup({ App, props, plugin }) {
            const ziggy = {
                ...(page.props.ziggy || {}),
                location: new URL(page.props.ziggy?.location || "http://localhost"),
            };

            // Pages call the global route() in <script setup> (in the browser it
            // comes from @routes). Node has no window, so provide it here.
            globalThis.route = (name, params, absolute, config = ziggy) =>
                ziggyRoute(name, params, absolute, config);

            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, ziggy)
                .component("Icon", Icon)
                .component("Head", Head)
                .component("Link", Link);
        },
    })
);
