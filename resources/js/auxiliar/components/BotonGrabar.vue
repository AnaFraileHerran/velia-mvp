<script setup lang="ts">
/**
 * Micro flotante (comentario de Antonio en A1-02): desde cualquier pantalla, eliges a quién y empiezas a grabar.
 * La lista se pide al abrir (prop opcional «paraGrabar»), con quien aún no tiene registro primero.
 */
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Mic } from '@lucide/vue'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

const page = usePage<any>()
const abierta = ref(false)
const cargando = ref(false)
const lista = computed<any[]>(() => page.props.paraGrabar ?? [])
const etiqueta: Record<string, string> = { pendiente: 'Por registrar', borrador: 'Borrador sin confirmar', registrado: 'Ya registrado' }

function abrir() {
    abierta.value = true
    cargando.value = true
    router.reload({ only: ['paraGrabar'], onFinish: () => (cargando.value = false) })
}
</script>

<template>
    <button
        type="button"
        class="fixed right-4 z-30 flex size-16 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-[0_8px_20px_rgb(15_61_149/0.35)] transition active:scale-95 md:right-8"
        style="bottom: calc(5.25rem + env(safe-area-inset-bottom))"
        aria-label="Grabar un registro"
        @click="abrir"
    >
        <Mic class="size-7" aria-hidden="true" />
    </button>

    <Sheet v-model:open="abierta">
        <SheetContent side="bottom" class="mx-auto max-h-[80dvh] max-w-xl rounded-t-2xl p-0">
            <SheetHeader class="px-5 pt-5">
                <SheetTitle>¿A quién vas a registrar?</SheetTitle>
                <SheetDescription>Toca su nombre y empieza a hablar. Primero, quien aún no tiene registro en este turno.</SheetDescription>
            </SheetHeader>
            <p v-if="cargando && !lista.length" class="px-5 pb-6 text-sm text-muted-foreground">Cargando…</p>
            <ul class="overflow-y-auto px-2 pb-4">
                <li v-for="r in lista" :key="r.id">
                    <Link :href="`/auxiliar/registrar/${r.id}?grabar=1`" class="flex min-h-14 items-center gap-3 rounded-xl px-3 py-2 hover:bg-accent focus-visible:bg-accent">
                        <Avatar class="size-10"><AvatarImage :src="r.foto" alt="" /><AvatarFallback>{{ r.nombre[0] }}</AvatarFallback></Avatar>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold">{{ r.nombre }}</span>
                            <span class="block text-sm text-muted-foreground">Hab. {{ r.habitacion }} · {{ etiqueta[r.estado] }}</span>
                        </span>
                        <Mic class="size-5 text-primary" aria-hidden="true" />
                    </Link>
                </li>
            </ul>
        </SheetContent>
    </Sheet>
</template>
