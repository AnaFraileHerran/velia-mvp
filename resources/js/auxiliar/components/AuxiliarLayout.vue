<script setup lang="ts">
/**
 * Marco de la app de la auxiliar (shadcn-vue): contenido centrado, barra inferior para el pulgar
 * «Mi planta · micro · Mi turno» (26/11/2026: Resumen y Residentes se fusionan en Mi planta y el micro pasa al centro de la barra),
 * aviso de «sin conexión», mensajes (toast) y bloqueo a los 5 minutos sin tocar la pantalla
 * (se cierra la sesión, el turno sigue abierto). Funciona a 390 px y en tablet horizontal (1194 × 834).
 */
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { CircleUser, House } from '@lucide/vue'
import { toast } from 'vue-sonner'
import 'vue-sonner/style.css'
import { Toaster } from '@/components/ui/sonner'
import BotonGrabar from './BotonGrabar.vue'
import SinConexion from './SinConexion.vue'
import GuiaPrimeraVez from './GuiaPrimeraVez.vue'

const page = usePage<any>()
const c = computed(() => page.props.contadores ?? {})
const url = computed(() => page.url)
const izquierda = computed(() => ({ href: '/auxiliar', label: 'Mi planta', icon: House, n: c.value.planta, activo: url.value === '/auxiliar' || /^\/auxiliar(\?|\/residente|\/registrar)/.test(url.value) }))
const derecha = computed(() => ({ href: '/auxiliar/mi-turno', label: 'Mi turno', icon: CircleUser, n: 0, activo: /^\/auxiliar\/(mi-turno|antes-de-irte)/.test(url.value) }))

watch(() => page.props.aviso, (a: any) => {
    if (a?.texto) (a.tipo === 'error' ? toast.error : toast.success)(a.texto)
}, { immediate: true })

// Tablet compartida: si nadie toca la pantalla en 5 minutos, se cierra la sesión (el turno sigue abierto).
let reloj: ReturnType<typeof setTimeout>
const reiniciar = () => { clearTimeout(reloj); reloj = setTimeout(() => router.post('/auxiliar/salir'), 5 * 60 * 1000) }
const eventos = ['pointerdown', 'keydown', 'scroll']
onMounted(() => { eventos.forEach((e) => window.addEventListener(e, reiniciar, { passive: true })); reiniciar() })
onBeforeUnmount(() => { eventos.forEach((e) => window.removeEventListener(e, reiniciar)); clearTimeout(reloj) })
</script>

<template>
    <div class="min-h-dvh pb-32">
        <main class="mx-auto w-full max-w-3xl px-4 pt-6 md:px-8 md:pt-8">
            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-30 border-t bg-card pb-[env(safe-area-inset-bottom)]" aria-label="Navegación del turno">
            <ul class="mx-auto grid max-w-3xl grid-cols-3 items-end">
                <li v-for="t in [izquierda, null, derecha]" :key="t ? t.href : 'micro'" class="flex justify-center">
                    <!-- En Registrar ya hay un micro grande: dos micros en la misma pantalla confunden -->
                    <BotonGrabar v-if="!t && !/^\/auxiliar\/(registrar|grabar)/.test(url)" />
                    <span v-else-if="!t" aria-hidden="true" />
                    <Link
                        v-else
                        :href="t.href"
                        :aria-current="t.activo ? 'page' : undefined"
                        class="flex min-h-14 w-full flex-col items-center justify-center gap-1 py-2 text-xs font-medium transition-colors"
                        :class="t.activo ? 'text-primary' : 'text-muted-foreground hover:text-foreground'"
                    >
                        <span class="relative">
                            <component :is="t.icon" class="size-6" aria-hidden="true" />
                            <span v-if="t.n" class="absolute -right-3 -top-1 min-w-4 rounded-full bg-primary px-1.5 text-center text-[10px] leading-4 text-primary-foreground">{{ t.n }}</span>
                        </span>
                        {{ t.label }}
                    </Link>
                </li>
            </ul>
        </nav>

        <SinConexion />
        <GuiaPrimeraVez />
        <Toaster position="top-center" rich-colors />
    </div>
</template>
