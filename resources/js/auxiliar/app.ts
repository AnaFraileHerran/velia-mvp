// App de la auxiliar con shadcn-vue (25/11/2026). Inertia + Vue 3 sobre la misma app de Laravel (sesión con PIN del guard «turno»).
import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AuxiliarLayout from './components/AuxiliarLayout.vue'

createInertiaApp({
    title: (t) => (t ? `${t} · Velia` : 'Velia'),
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue', { eager: true })
        const page = pages[`./Pages/${name}.vue`]
        // Todas las pantallas llevan la barra inferior, menos entrar con PIN.
        page.default.layout ??= name === 'Entrar' ? undefined : AuxiliarLayout
        return page
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el)
    },
    progress: { color: '#0F3D95' },
})
