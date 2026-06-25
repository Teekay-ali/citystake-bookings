import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

const appName = import.meta.env.VITE_APP_NAME || 'Citystake';

// When an Inertia XHR receives a non-Inertia HTML response (e.g. a Cloudflare
// "verify you are human" challenge), Inertia would normally show it in an
// overlay modal. Detect that case and do a full page reload instead so the
// browser handles the challenge natively and lands back on the real page.
router.on('invalid', (event) => {
    const response = event.detail.response;
    const body = typeof response?.data === 'string' ? response.data : '';

    const looksLikeCloudflare =
        response?.status === 403 ||
        response?.status === 503 ||
        /cloudflare|cf-browser-verification|challenge-platform|just a moment/i.test(body);

    if (!looksLikeCloudflare) {
        return; // genuine error — let Inertia handle it normally
    }

    event.preventDefault();

    // Guard against reload loops if the challenge keeps returning
    const key = 'cf-reload-at';
    const last = Number(sessionStorage.getItem(key) || 0);
    if (Date.now() - last > 10000) {
        sessionStorage.setItem(key, String(Date.now()));
        window.location.reload();
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, {
                position: "bottom-right",
                timeout: 4000,
                closeOnClick: false,
                pauseOnFocusLoss: true,
                pauseOnHover: true,
                draggable: false,
                showCloseButtonOnHover: false,
                hideProgressBar: true,
                closeButton: "button",
                icon: false,
                rtl: false,
                transition: "Vue-Toastification__fade",
                maxToasts: 3,
                newestOnTop: true,
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
