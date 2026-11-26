<script setup lang="ts">
/**
 * Fila de residente de «Mi planta»: foto, nombre y habitación, y lo último que se sabe.
 * Si tiene novedad, la novedad va dentro de la fila (quién la anotó y a qué hora), no en otro bloque (comentario de Pablo en A1-03).
 * Se toca para abrir su ficha (página). El micro de la derecha lleva directo a registrar.
 */
import { Link } from '@inertiajs/vue3'
import { ChevronRight, Mic } from '@lucide/vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'

defineProps<{ residente: any; texto?: string | null; detalle?: string | null; incidencia?: boolean; etiqueta?: string | null; micro?: boolean }>()
</script>

<template>
    <div class="flex items-center" :class="incidencia && 'bg-[color-mix(in_oklch,var(--destructive)_6%,white)]'">
        <Link :href="`/auxiliar/residente/${residente.id}`" class="flex min-h-16 min-w-0 flex-1 items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-accent focus-visible:bg-accent">
            <Avatar class="size-11"><AvatarImage :src="residente.foto" alt="" /><AvatarFallback>{{ residente.nombre[0] }}</AvatarFallback></Avatar>
            <span class="min-w-0 flex-1">
                <span class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold">{{ residente.nombre }}</span>
                    <Badge v-if="incidencia" variant="destructive">Incidencia</Badge>
                    <Badge v-if="etiqueta" variant="secondary">{{ etiqueta }}</Badge>
                </span>
                <span class="block text-sm text-muted-foreground">Hab. {{ residente.habitacion }}</span>
                <span v-if="texto" class="block text-sm text-foreground/85">{{ texto }}</span>
                <span v-if="detalle" class="block text-xs text-muted-foreground">{{ detalle }}</span>
            </span>
            <ChevronRight v-if="!micro" class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
        </Link>
        <Link v-if="micro" :href="`/auxiliar/registrar/${residente.id}?grabar=1`" class="mr-3 flex size-12 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-sm transition active:scale-95" :aria-label="`Registrar a ${residente.nombre}`">
            <Mic class="size-5" aria-hidden="true" />
        </Link>
    </div>
</template>
