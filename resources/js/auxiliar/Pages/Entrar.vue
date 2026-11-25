<script setup lang="ts">
/**
 * Entrar con PIN. Tablet: «Te reconoceremos al escribirlo» (el PIN identifica a la auxiliar).
 * Móvil vinculado: «Hola, Lucía». Regla 3: si se olvida, la responsable de turno da uno nuevo.
 */
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { Delete } from '@lucide/vue'
import { toast } from 'vue-sonner'
import 'vue-sonner/style.css'
import { Toaster } from '@/components/ui/sonner'
import { Button } from '@/components/ui/button'

const props = defineProps<{ lema?: string | null; vinculada?: string | null; esMovil: boolean; fecha: string; rutas: { olvide: string; primerDia: string } }>()
const page = usePage<any>()
const form = useForm({ pin: '' })
const teclas = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', 'borrar']
const puntos = computed(() => [0, 1, 2, 3].map((i) => i < form.pin.length))

function pulsar(t: string) {
    if (form.processing) return
    if (t === 'borrar') { form.pin = form.pin.slice(0, -1); return }
    if (!t || form.pin.length >= 4) return
    form.clearErrors()
    form.pin += t
    if (form.pin.length === 4) form.post('/auxiliar/entrar', { onError: () => form.reset('pin') })
}
const teclado = (e: KeyboardEvent) => { if (/^\d$/.test(e.key)) pulsar(e.key); else if (e.key === 'Backspace') pulsar('borrar') }
onMounted(() => window.addEventListener('keydown', teclado))
onBeforeUnmount(() => window.removeEventListener('keydown', teclado))
watch(() => page.props.aviso, (a: any) => { if (a?.texto) toast.success(a.texto) }, { immediate: true })
</script>

<template>
    <Head title="Entrar" />
    <main class="mx-auto flex min-h-dvh w-full max-w-sm flex-col items-center justify-center gap-7 px-6 py-10 text-center">
        <div>
            <img src="/favicon.svg" alt="" class="mx-auto mb-3 size-10" />
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ page.props.residencia?.nombre }}</p>
            <p v-if="lema" class="text-sm text-muted-foreground">{{ lema }}</p>
        </div>

        <div>
            <h1 v-if="vinculada" class="text-2xl font-semibold">Hola, {{ vinculada }}</h1>
            <p v-else class="text-sm text-muted-foreground">{{ fecha }}</p>
            <h2 class="mt-1 text-xl font-semibold">Introduce tu PIN</h2>
            <p v-if="!vinculada" class="text-sm text-muted-foreground">Te reconoceremos al escribirlo.</p>
        </div>

        <div class="flex gap-4" aria-hidden="true">
            <span v-for="(lleno, i) in puntos" :key="i" class="size-4 rounded-full border-2 border-primary transition-colors" :class="lleno ? 'bg-primary' : 'bg-transparent'" />
        </div>
        <p class="sr-only" aria-live="polite">{{ form.pin.length }} de 4 cifras</p>

        <div class="grid w-full grid-cols-3 gap-3" role="group" aria-label="Teclado del PIN">
            <template v-for="t in teclas" :key="t">
                <span v-if="!t" />
                <Button v-else-if="t === 'borrar'" variant="ghost" class="h-16 rounded-2xl text-xl" aria-label="Borrar" @click="pulsar(t)"><Delete class="size-6" /></Button>
                <Button v-else variant="secondary" class="h-16 rounded-2xl text-2xl font-semibold text-foreground" @click="pulsar(t)">{{ t }}</Button>
            </template>
        </div>

        <p v-if="form.errors.pin" role="alert" class="text-sm font-medium text-destructive">{{ form.errors.pin }}</p>

        <div class="flex flex-col items-center gap-2 text-sm">
            <p v-if="esMovil" class="text-muted-foreground">¿Olvidaste tu PIN? Pídeselo a la responsable de turno.</p>
            <a v-else :href="rutas.olvide" class="font-medium text-primary underline-offset-4 hover:underline">¿Olvidaste tu PIN?</a>
            <a :href="rutas.primerDia" class="text-muted-foreground underline-offset-4 hover:underline">Es mi primer día</a>
        </div>
    </main>
    <Toaster position="top-center" rich-colors />
</template>
