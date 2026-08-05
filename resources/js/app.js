import '../css/app.css'
import 'vue-sonner/style.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'

// Inisialisasi tema sebelum mount (hindari flash)
const storedTheme = localStorage.getItem('theme') || 'light'
if (storedTheme === 'dark') {
    document.documentElement.classList.add('dark')
}

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .component('Toaster', Toaster)
            .mount(el)
    },
    progress: {
        color: '#E53E3E',
    },
})