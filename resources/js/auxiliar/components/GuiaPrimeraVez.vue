<script setup lang="ts">
/**
 * Guía de la primera vez (26/11/2026). Decisiones de UX:
 *  · Sobre la app real, no en un carrusel aparte: se oscurece la pantalla y se ilumina la pieza de la barra de abajo de la que se habla
 *    (Mi planta → micro → Mi turno). Así se aprende dónde está cada cosa, no solo qué es.
 *  · Corta: bienvenida + 3 pasos, una frase cada uno, en el idioma del turno. Se puede saltar siempre.
 *  · Una vez por persona (guardado en su usuario, no en el dispositivo: la tablet es compartida). Se vuelve a ver desde «Mi turno».
 *  · Recoge lo que contaba la landing de auxiliares, que se aparca (decisión 5).
 */
import { computed, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { CircleUser, House, Mic, Sparkles } from '@lucide/vue'
import { Button } from '@/components/ui/button'

const page = usePage<any>()
const abierta = ref(!!page.props.guia)
const paso = ref(0)
watch(() => page.props.guia, (v) => { if (v) { abierta.value = true; paso.value = 0 } })
// «Ver la guía» desde Mi turno
window.addEventListener('velia-guia', () => { abierta.value = true; paso.value = 0 })

const nombre = computed(() => page.props.yo?.corto ?? '')
const pasos = computed(() => [
    { foco: null, icono: Sparkles, titulo: `Hola, ${nombre.value}. Esto es Velia`, texto: 'Te ayuda a registrar los cuidados hablando, sin papeles. En tres pasos te enseño dónde está cada cosa.' },
    { foco: 0, icono: House, titulo: 'Mi planta', texto: 'Tus residentes en una lista. Arriba, lo que te piden y quién tiene novedad. Toca a alguien para ver cómo atenderle.' },
    { foco: 1, icono: Mic, titulo: 'El micro', texto: 'Tócalo y cuenta cómo ha ido: Velia lo ordena y tú lo confirmas. Si el micro o la red fallan, puedes escribirlo.' },
    { foco: 2, icono: CircleUser, titulo: 'Mi turno', texto: 'Al final, marca de una vez a quien no ha tenido novedad. «Salir» deja la tablet lista para tu compañera.' },
])
const actual = computed(() => pasos.value[paso.value])
const ultimo = computed(() => paso.value === pasos.value.length - 1)

function terminar() {
    abierta.value = false
    router.post('/auxiliar/guia-vista', {}, { preserveScroll: true, preserveState: true })
}
function siguiente() { ultimo.value ? terminar() : paso.value++ }

// Posición del foco: las 3 columnas de la barra inferior
const focoEstilo = computed(() => {
    const f = actual.value.foco
    if (f === null) return null
    // El micro sobresale de la barra: su foco sube un poco más
    return { left: `calc(${(f * 100) / 3}% + 0.5rem)`, width: `calc(${100 / 3}% - 1rem)`, top: f === 1 ? '-1.75rem' : '0.25rem', boxShadow: '0 0 0 4px #fff, 0 0 0 9999px rgba(0,0,0,0.6)' }
})
</script>

<template>
    <Teleport to="body">
        <div v-if="abierta" class="fixed inset-0 z-50" role="dialog" aria-modal="true" :aria-label="`Guía · paso ${paso + 1} de ${pasos.length}`">
            <!-- Fondo oscuro; el hueco ilumina la pieza de la barra de abajo -->
            <div class="absolute inset-0" :class="!focoEstilo && 'bg-black/60'" />
            <!-- El foco oscurece todo lo demás con una sombra enorme: la pieza de la barra queda a la vista y rodeada -->
            <div v-if="focoEstilo" class="pointer-events-none absolute inset-x-0 bottom-0 mx-auto h-[calc(4rem+env(safe-area-inset-bottom))] max-w-3xl">
                <div class="absolute bottom-[calc(0.25rem+env(safe-area-inset-bottom))] rounded-2xl transition-all duration-300" :style="focoEstilo" />
            </div>

            <!-- Tarjeta: abajo en el móvil (cerca del pulgar y de lo que señala), centrada en tablet -->
            <div class="absolute inset-x-0 mx-auto w-full max-w-md px-4" :class="focoEstilo ? 'bottom-28' : 'top-1/2 -translate-y-1/2'">
                <div class="space-y-4 rounded-2xl bg-card p-6 shadow-xl">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-secondary text-primary"><component :is="actual.icono" class="size-6" aria-hidden="true" /></span>
                        <h2 class="text-xl font-semibold leading-tight">{{ actual.titulo }}</h2>
                    </div>
                    <p class="text-base leading-relaxed text-foreground/85">{{ actual.texto }}</p>
                    <div class="flex items-center justify-between gap-3 pt-1">
                        <div class="flex gap-1.5" aria-hidden="true">
                            <span v-for="(p, i) in pasos" :key="i" class="h-2 rounded-full transition-all" :class="i === paso ? 'w-6 bg-primary' : 'w-2 bg-muted-foreground/30'" />
                        </div>
                        <div class="flex items-center gap-2">
                            <Button v-if="!ultimo" variant="ghost" class="h-11 rounded-full" @click="terminar">Saltar</Button>
                            <Button class="h-11 rounded-full px-6 text-base" @click="siguiente">{{ paso === 0 ? 'Empezar' : ultimo ? 'Entendido' : 'Siguiente' }}</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
